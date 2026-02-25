<?php

namespace App\Http\Controllers;

use App\Enums\BetType;
use App\Models\Race;
use App\Models\Bet;
use App\Models\BetItem;
use Illuminate\Http\Request;
use App\Services\Bet\CartService;
use App\Services\Bet\BuilderResolver;
use App\Services\BetSettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BetFlowController extends Controller
{
    private function ensureRaceBettingOpen(Race $race)
    {
        if (!$race->is_betting_closed) {
            return null;
        }

        return redirect()
            ->route('bet.races')
            ->with('error', 'このレースは投票終了のため購入できません。');
    }

    private function cartKey(int $raceId): string
    {
        // レースごとにカートを分ける（他レースを誤爆しない）
        // NOTE: ドット区切りは既存セッション構造と衝突する場合があるため、フラットキーで保持する
        return "bet_cart_{$raceId}";
    }

    public function selectRace()
    {
        $races = \App\Models\Race::withCount('payouts')
            ->orderBy('race_date', 'asc')
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get();

        return view('bet.races', compact('races'));
    }

    public function cart(Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        session(['bet.current_race_id' => $race->id]);
        $cart = session($this->cartKey($race->id), [
            'race_id' => $race->id,
            'items' => [],
            'groups' => [],
        ]);
        Log::info('bet.cart.view', [
            'user_id' => auth()->id(),
            'race_id' => $race->id,
            'session_id' => session()->getId(),
            'items_count' => count($cart['items'] ?? []),
        ]);

        return view('bet.cart', compact('race', 'cart'));
    }

    public function cartUpdate(Request $request, Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        $request->validate([
            'items.*.amount' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($value % 100 !== 0) {
                        $fail('金額は100円単位で入力してください。');
                    }
                },
            ],
        ]);

        $cartKey = $this->cartKey($race->id);
        $cart = session($cartKey, ['race_id' => $race->id, 'items' => [], 'groups' => []]);

        $action = (string)$request->input('action', 'update_amount');
        if ($action === 'update_amount' && $request->filled('index')) {
            $action = 'remove';
        }

        // 金額更新（0円は削除）
        if ($action === 'update_amount') {
            $amountMax = (int) config('domain.bet.amount.max', 1_000_000);

            $validated = $request->validate([
                'items' => ['required', 'array'],
                'items.*.amount' => [
                    'required',
                    'integer',
                    'min:0',
                    "max:{$amountMax}",
                    function ($attribute, $value, $fail) {
                        if ($value % 100 !== 0) {
                            $fail('金額は100円単位で入力してください。');
                        }
                    },
                ],
            ]);

            foreach ($cart['items'] as $i => $item) {
                if (isset($validated['items'][$i]['amount'])) {
                    $cart['items'][$i]['amount'] = (int)$validated['items'][$i]['amount'];
                }
            }

            // 0円は行ごと削除（気持ちいい）
            $cart['items'] = array_values(array_filter($cart['items'], fn($row) => (int)$row['amount'] > 0));

            session([$cartKey => $cart]);
            return redirect()->route('bet.cart', $race)->with('success', '金額を更新しました');
        }

        // 1行削除（index指定）
        if ($action === 'remove') {
            $validated = $request->validate([
                'index' => ['required', 'integer', 'min:0'],
            ]);

            $index = (int)$validated['index'];
            if (isset($cart['items'][$index])) {
                unset($cart['items'][$index]);
                $cart['items'] = array_values($cart['items']);
                session([$cartKey => $cart]);
            }

            return redirect()->route('bet.cart', $race)->with('success', '削除しました');
        }

        // 全削除
        if ($action === 'clear') {
            session()->forget($cartKey);
            return redirect()->route('bet.cart', $race)->with('success', 'カートを空にしました');
        }

        return redirect()->route('bet.cart', $race);
    }


    public function commit(Request $request, Race $race, BetSettlementService $settlementService)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        $cartKey = $this->cartKey($race->id);
        $cart = session($cartKey);

        if (!$cart || empty($cart['items'])) {
            return redirect()->route('bet.cart', $race)->with('error', 'カートが空です');
        }

        // 決定時に入力欄の最新金額を反映（更新ボタン押し忘れ対策）
        if ($request->has('items')) {
            $amountMax = (int) config('domain.bet.amount.max', 1_000_000);
            $validated = $request->validate([
                'items' => ['required', 'array'],
                'items.*.amount' => [
                    'required',
                    'integer',
                    'min:0',
                    "max:{$amountMax}",
                    function ($attribute, $value, $fail) {
                        if ($value % 100 !== 0) {
                            $fail('金額は100円単位で入力してください。');
                        }
                    },
                ],
            ]);

            foreach ($cart['items'] as $i => $item) {
                if (isset($validated['items'][$i]['amount'])) {
                    $cart['items'][$i]['amount'] = (int) $validated['items'][$i]['amount'];
                }
            }

            $cart['items'] = array_values(array_filter($cart['items'], fn ($row) => (int) $row['amount'] > 0));
            session([$cartKey => $cart]);

            if (empty($cart['items'])) {
                return redirect()->route('bet.cart', $race)->with('error', 'カートが空です');
            }
        }

        DB::transaction(function () use ($race, $cart) {
            $stakeAmount = (int)collect($cart['items'])->sum(fn($i) => (int)$i['amount']);
            $buildSnapshot = $this->buildSnapshotFromCart($cart);

            $bet = Bet::create([
                'user_id' => auth()->id(),
                'race_id' => $race->id,
                'stake_amount' => $stakeAmount,
                'return_amount' => 0,
                'hit_count' => 0,
                'roi_percent' => 0,
                'build_snapshot' => $buildSnapshot,
                // bought_at は model側で自動 now()
            ]);

            $rows = collect($cart['items'])->map(fn($i) => [
                'bet_id' => $bet->id,
                'bet_type' => $i['bet_type'],
                'selection_key' => $i['selection_key'],
                'amount' => (int)$i['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            BetItem::insert($rows);
        });

        // すでに払戻が登録済みのレースなら購入直後に結果反映
        $settlementService->recalculateForRace($race->id);

        // 確定したら編集不可：カートを消す（以後はDB参照のみ）
        session()->forget($cartKey);

        return redirect()->route('bet.races')->with('success', '購入を確定しました');
    }

    public function cartAdd(Request $request, Race $race, CartService $cartService, BuilderResolver $resolver)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        $betType = (string) $request->input('betType', 'sanrentan');
        $modeRaw = (string) $request->input('mode', '');

        // 互換（既存のmode名が残っても動くようにする）
        $mode = match ([$betType, $modeRaw]) {
            ['sanrentan', 'sanrentan_box'] => 'box',
            ['sanrentan', 'sanrentan_formation'] => 'formation',
            default => $modeRaw,
        };

        $builder = $resolver->resolve($betType, $mode);

        // validationもBuilder側
        $validated = $builder->validate($request, $race);

        // items生成もBuilder側
        $items = $builder->build($validated, $race);
        Log::info('bet.cart.add.build', [
            'user_id' => auth()->id(),
            'race_id' => $race->id,
            'bet_type' => $betType,
            'mode' => $mode,
            'session_id' => session()->getId(),
            'built_items_count' => count($items),
        ]);

        if (empty($items)) {
            return back()
                ->withErrors(['cart_add' => '有効な買い目がありません。列の選択条件を確認してください。'])
                ->withInput();
        }

        $cartService->addItems($race->id, $items);
        $this->appendSnapshotGroup($race->id, $betType, $mode, $validated, $items);
        $savedCart = session($this->cartKey($race->id), ['items' => [], 'groups' => []]);
        Log::info('bet.cart.add.saved', [
            'user_id' => auth()->id(),
            'race_id' => $race->id,
            'session_id' => session()->getId(),
            'saved_items_count' => count($savedCart['items'] ?? []),
        ]);

        return redirect()->route('bet.cart', $race)->with('success', count($items) . '点をカートに追加しました');
    }


    public function selectType(Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        session(['bet.current_race_id' => $race->id]);

        $types = config('bets.types', []);
        $typeOrder = array_map(
            fn(BetType $type) => $type->value,
            BetType::all()
        );

        $sortedTypes = [];
        foreach ($typeOrder as $key) {
            if (isset($types[$key])) {
                $sortedTypes[$key] = $types[$key];
            }
        }
        foreach ($types as $key => $type) {
            if (!isset($sortedTypes[$key])) {
                $sortedTypes[$key] = $type;
            }
        }

        // types が空なら 404 にしてもいいけど、今は画面に出す
        return view('bet.types', [
            'race' => $race,
            'types' => $sortedTypes,
        ]);
    }


    public function selectMode(Race $race, string $betType)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        session(['bet.current_race_id' => $race->id]);

        $types = config('bets.types', []);
        abort_unless(isset($types[$betType]), 404);

        $typeLabel = $types[$betType]['label'] ?? $betType;
        $modes = $types[$betType]['modes'] ?? [];

        return view('bet.modes', compact('race', 'betType', 'typeLabel', 'modes'));
    }



    public function buildByMode(Race $race, string $betType, string $mode)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        session(['bet.current_race_id' => $race->id]);
        // 互換：現状 mode が sanrentan_box のままでも動くようにする
        $mode = match ([$betType, $mode]) {
            ['sanrentan', 'sanrentan_box'] => 'box',
            default => $mode,
        };

        $types = config('bets.types', []);
        $conf  = $types[$betType]['modes'][$mode] ?? null;
        abort_if(!$conf, 404);

        $count = (int)($race->horse_count ?? config('domain.bet.default_horse_count', 18));
        $horseNos = collect(range(1, $count))
            ->map(fn($n) => (string)$n)
            ->values();

        return view($conf['view'], [
            'race' => $race,
            'betType' => $betType,
            'mode' => $mode,
            'horseNos' => $horseNos,
        ]);
    }

    private function appendSnapshotGroup(int $raceId, string $betType, string $mode, array $validated, array $items): void
    {
        $cartKey = $this->cartKey($raceId);
        $cart = session($cartKey, ['race_id' => $raceId, 'items' => [], 'groups' => []]);

        $itemKeys = collect($items)
            ->map(fn (array $row) => $this->itemKey((string) $row['bet_type'], (string) $row['selection_key']))
            ->values()
            ->all();
        $unitAmount = $this->detectUnitAmount($items);
        $input = $this->extractSnapshotInput($validated);

        $cart['groups'] ??= [];
        $cart['groups'][] = [
            'bet_type' => $betType,
            'mode' => $mode,
            'input' => $input,
            'item_keys' => $itemKeys,
            'point_count' => count($items),
            'unit_amount' => $unitAmount,
            'total_amount' => (int) collect($items)->sum(fn (array $row) => (int) ($row['amount'] ?? 0)),
        ];

        session([$cartKey => $cart]);
    }

    private function buildSnapshotFromCart(array $cart): array
    {
        $groups = collect($cart['groups'] ?? [])
            ->map(function (array $group) {
                return [
                    'bet_type' => (string) ($group['bet_type'] ?? ''),
                    'mode' => (string) ($group['mode'] ?? ''),
                    'input' => is_array($group['input'] ?? null) ? $group['input'] : [],
                    'point_count' => (int) ($group['point_count'] ?? 0),
                    'unit_amount' => isset($group['unit_amount']) ? (int) $group['unit_amount'] : null,
                    'total_amount' => (int) ($group['total_amount'] ?? 0),
                ];
            })
            ->filter(fn (array $group) => $group['bet_type'] !== '' && $group['point_count'] > 0)
            ->values()
            ->all();

        if (empty($groups)) {
            $fallbackByType = collect($cart['items'] ?? [])
                ->groupBy('bet_type')
                ->map(function ($rows, $betType) {
                    $unitAmount = $this->detectUnitAmount($rows->all());
                    return [
                        'bet_type' => (string) $betType,
                        'mode' => 'unknown',
                        'input' => [
                            'selection_keys' => $rows->pluck('selection_key')->values()->all(),
                        ],
                        'point_count' => $rows->count(),
                        'unit_amount' => $unitAmount,
                        'total_amount' => (int) $rows->sum(fn ($row) => (int) ($row['amount'] ?? 0)),
                    ];
                })
                ->values()
                ->all();

            $groups = $fallbackByType;
        }

        return [
            'version' => 1,
            'groups' => $groups,
        ];
    }

    private function extractSnapshotInput(array $validated): array
    {
        $keys = ['horse', 'horses', 'frames', 'first', 'second', 'third', 'axis', 'axis1', 'axis2', 'opponents', 'selection_keys'];
        $input = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];
            if (is_array($value)) {
                $input[$key] = collect($value)
                    ->map(fn ($v) => (string) $v)
                    ->unique()
                    ->values()
                    ->all();
            } else {
                $input[$key] = (string) $value;
            }
        }

        return $input;
    }

    private function detectUnitAmount(array $items): ?int
    {
        $amounts = collect($items)
            ->map(fn ($row) => (int) ($row['amount'] ?? 0))
            ->filter(fn (int $amount) => $amount > 0)
            ->unique()
            ->values();

        return $amounts->count() === 1 ? (int) $amounts->first() : null;
    }

    private function itemKey(string $betType, string $selectionKey): string
    {
        return $betType . '|' . $selectionKey;
    }
}
