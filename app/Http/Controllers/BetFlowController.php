<?php

namespace App\Http\Controllers;

use App\Enums\BetType;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Bet\CartService;
use App\Services\Bet\BuilderResolver;
use App\Services\Bet\BetPurchaseService;
use App\Services\Finance\BetMoneyService;
use App\Services\BetSettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BetFlowController extends Controller
{
    private const CHALLENGE_NORMAL = 'normal';
    private const CHALLENGE_RACE = 'challenge';

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

    private function commitTokenKey(int $raceId): string
    {
        return "bet_commit_token_{$raceId}";
    }

    private function ensureCommitToken(int $raceId): string
    {
        $key = $this->commitTokenKey($raceId);
        $token = session($key);

        if (!is_string($token) || $token === '') {
            $token = (string) Str::uuid();
            session([$key => $token]);
        }

        return $token;
    }

    private function challengeChoiceForUserRace(int $userId, int $raceId): ?string
    {
        return RaceUserAdjustment::query()
            ->where('user_id', $userId)
            ->where('race_id', $raceId)
            ->value('challenge_choice');
    }

    private function ensureChallengeChoiceSelected(Race $race)
    {
        $choice = $this->challengeChoiceForUserRace((int) auth()->id(), (int) $race->id);
        if ($choice !== null) {
            return null;
        }

        return redirect()
            ->route('bet.challenge.select', $race)
            ->with('error', 'このレースは最初に勝負レース宣言の選択が必要です。');
    }

    public function selectRace()
    {
        $userId = (int) auth()->id();
        $races = \App\Models\Race::withCount('payouts')
            ->orderBy('race_date', 'asc')
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get();

        $challengeChoices = RaceUserAdjustment::query()
            ->where('user_id', $userId)
            ->whereIn('race_id', $races->pluck('id'))
            ->pluck('challenge_choice', 'race_id');

        return view('bet.races', compact('races', 'challengeChoices'));
    }

    public function selectChallenge(Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        $choice = $this->challengeChoiceForUserRace((int) auth()->id(), (int) $race->id);
        if ($choice !== null) {
            return redirect()->route('bet.types', $race);
        }

        return view('bet.challenge_select', [
            'race' => $race,
        ]);
    }

    public function storeChallengeChoice(Request $request, Race $race, BetMoneyService $betMoneyService)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }

        $validated = $request->validate([
            'challenge_choice' => ['required', 'in:' . self::CHALLENGE_NORMAL . ',' . self::CHALLENGE_RACE],
        ], [
            'challenge_choice.required' => '通常か勝負レースかを選択してください。',
            'challenge_choice.in' => '選択内容が不正です。',
        ]);

        $userId = (int) auth()->id();
        $choice = (string) $validated['challenge_choice'];

        DB::transaction(function () use ($userId, $race, $choice, $betMoneyService) {
            $adjustment = RaceUserAdjustment::query()
                ->lockForUpdate()
                ->firstOrNew([
                    'user_id' => $userId,
                    'race_id' => $race->id,
                ]);

            if ($adjustment->challenge_choice === null) {
                $user = User::query()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $allowance = $betMoneyService->allowanceForChoice($choice);

                $adjustment->bonus_points = (int) ($adjustment->bonus_points ?? 0);
                $adjustment->challenge_choice = $choice;
                $adjustment->challenge_chosen_at = now();
                $adjustment->save();

                $user->current_balance = (int) ($user->current_balance ?? 0) + $allowance;
                $user->save();
            }
        });

        return redirect()->route('bet.types', $race);
    }

    public function cart(Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
            return $response;
        }

        session(['bet.current_race_id' => $race->id]);
        $commitToken = $this->ensureCommitToken((int) $race->id);
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

        return view('bet.cart', compact('race', 'cart', 'commitToken'));
    }

    public function cartUpdate(Request $request, Race $race)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
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

        $selectedIndexes = collect($request->input('selected_indexes', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn (int $v) => $v >= 0)
            ->unique()
            ->values();

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
                    $oldAmount = (int) ($cart['items'][$i]['amount'] ?? 0);
                    $newAmount = (int) $validated['items'][$i]['amount'];
                    $cart['items'][$i]['amount'] = $newAmount;

                    if ($oldAmount !== $newAmount) {
                        $cart['amount_changes'][] = [
                            'bet_type' => (string) ($cart['items'][$i]['bet_type'] ?? ''),
                            'selection_key' => (string) ($cart['items'][$i]['selection_key'] ?? ''),
                            'old_amount' => $oldAmount,
                            'new_amount' => $newAmount,
                            'changed_at' => now()->toIso8601String(),
                            'changed_by' => 'cart_update',
                        ];
                    }
                }
            }

            // 0円は行ごと削除（気持ちいい）
            $removedItems = collect($cart['items'])
                ->filter(fn ($row) => (int) ($row['amount'] ?? 0) === 0)
                ->map(fn (array $row) => $this->buildRemovedItemLog($row, 'update_amount_zero'))
                ->filter()
                ->values()
                ->all();

            $cart['items'] = array_values(array_filter($cart['items'], fn($row) => (int)$row['amount'] > 0));
            $cart['removed_items'] = array_values(array_merge($cart['removed_items'] ?? [], $removedItems));

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
                $removed = $this->buildRemovedItemLog($cart['items'][$index], 'remove');
                unset($cart['items'][$index]);
                $cart['items'] = array_values($cart['items']);
                if ($removed !== null) {
                    $cart['removed_items'] = array_values(array_merge($cart['removed_items'] ?? [], [$removed]));
                }
                session([$cartKey => $cart]);
            }

            return redirect()->route('bet.cart', $race)->with('success', '削除しました');
        }

        // 全削除
        if ($action === 'clear') {
            session()->forget($cartKey);
            return redirect()->route('bet.cart', $race)->with('success', 'カートを空にしました');
        }

        // 選択削除
        if ($action === 'selected_remove') {
            if ($selectedIndexes->isEmpty()) {
                return redirect()->route('bet.cart', $race)->with('error', '削除対象を選択してください。');
            }

            $removedItems = collect($cart['items'])
                ->filter(fn ($row, $idx) => $selectedIndexes->contains((int) $idx))
                ->map(fn (array $row) => $this->buildRemovedItemLog($row, 'selected_remove'))
                ->filter()
                ->values()
                ->all();

            $cart['items'] = collect($cart['items'])
                ->reject(fn ($row, $idx) => $selectedIndexes->contains((int) $idx))
                ->values()
                ->all();

            $cart['removed_items'] = array_values(array_merge($cart['removed_items'] ?? [], $removedItems));

            session([$cartKey => $cart]);

            return redirect()->route('bet.cart', $race)->with('success', '選択した買い目を削除しました');
        }

        // 選択以外削除
        if ($action === 'unselected_remove') {
            if ($selectedIndexes->isEmpty()) {
                return redirect()->route('bet.cart', $race)->with('error', '残したい買い目を選択してください。');
            }

            $removedItems = collect($cart['items'])
                ->reject(fn ($row, $idx) => $selectedIndexes->contains((int) $idx))
                ->map(fn (array $row) => $this->buildRemovedItemLog($row, 'unselected_remove'))
                ->filter()
                ->values()
                ->all();

            $cart['items'] = collect($cart['items'])
                ->filter(fn ($row, $idx) => $selectedIndexes->contains((int) $idx))
                ->values()
                ->all();
            $cart['removed_items'] = array_values(array_merge($cart['removed_items'] ?? [], $removedItems));

            session([$cartKey => $cart]);

            return redirect()->route('bet.cart', $race)->with('success', '選択以外の買い目を削除しました');
        }

        return redirect()->route('bet.cart', $race);
    }


    public function commit(
        Request $request,
        Race $race,
        BetSettlementService $settlementService,
        BetPurchaseService $betPurchaseService
    )
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
            return $response;
        }

        $cartKey = $this->cartKey($race->id);
        $commitTokenKey = $this->commitTokenKey((int) $race->id);
        $expectedCommitToken = session($commitTokenKey);
        $cart = session($cartKey);

        if (!$cart || empty($cart['items'])) {
            return redirect()->route('bet.cart', $race)->with('error', 'カートが空です');
        }

        if (!is_string($expectedCommitToken) || $expectedCommitToken === '') {
            $expectedCommitToken = $this->ensureCommitToken((int) $race->id);
        }

        $requestCommitToken = (string) $request->input('idempotency_key', (string) $expectedCommitToken);
        if ($requestCommitToken !== $expectedCommitToken) {
            return redirect()->route('bet.cart', $race)->with('error', '画面を再読み込みしてから再度お試しください。');
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
                    $oldAmount = (int) ($cart['items'][$i]['amount'] ?? 0);
                    $newAmount = (int) $validated['items'][$i]['amount'];
                    $cart['items'][$i]['amount'] = $newAmount;

                    if ($oldAmount !== $newAmount) {
                        $cart['amount_changes'][] = [
                            'bet_type' => (string) ($cart['items'][$i]['bet_type'] ?? ''),
                            'selection_key' => (string) ($cart['items'][$i]['selection_key'] ?? ''),
                            'old_amount' => $oldAmount,
                            'new_amount' => $newAmount,
                            'changed_at' => now()->toIso8601String(),
                            'changed_by' => 'commit',
                        ];
                    }
                }
            }

            $cart['items'] = array_values(array_filter($cart['items'], fn ($row) => (int) $row['amount'] > 0));
            session([$cartKey => $cart]);

            if (empty($cart['items'])) {
                return redirect()->route('bet.cart', $race)->with('error', 'カートが空です');
            }
        }

        try {
            $buildSnapshot = $this->buildSnapshotFromCart($cart);
            $isDuplicate = $betPurchaseService->commit(
                (int) auth()->id(),
                (int) $race->id,
                $cart['items'],
                $buildSnapshot,
                $requestCommitToken
            );

            if ($isDuplicate) {
                session()->forget($cartKey);
                session()->forget($commitTokenKey);

                return redirect()->route('bet.races')->with('success', '購入を確定しました。');
            }
        } catch (ValidationException $e) {
            return redirect()->route('bet.cart', $race)
                ->withErrors($e->errors())
                ->withInput();
        }

        // すでに払戻が登録済みのレースなら購入直後に結果反映
        $settlementService->recalculateForRace($race->id);

        // 確定したら編集不可：カートを消す（以後はDB参照のみ）
        session()->forget($cartKey);
        session()->forget($commitTokenKey);

        return redirect()->route('bet.races')->with('success', '購入を確定しました');
    }

    public function cartAdd(Request $request, Race $race, CartService $cartService, BuilderResolver $resolver)
    {
        if ($response = $this->ensureRaceBettingOpen($race)) {
            return $response;
        }
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
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
        $itemCount = count($items);
        $pointCountMax = (int) config('domain.bet.point_count_max', 1_000);
        Log::info('bet.cart.add.build', [
            'user_id' => auth()->id(),
            'race_id' => $race->id,
            'bet_type' => $betType,
            'mode' => $mode,
            'session_id' => session()->getId(),
            'built_items_count' => $itemCount,
        ]);

        if (empty($items)) {
            return back()
                ->withErrors(['cart_add' => '有効な買い目がありません。列の選択条件を確認してください。'])
                ->withInput();
        }

        if ($itemCount > $pointCountMax) {
            return back()
                ->withErrors(['cart_add' => "まとめてカートに追加できる点数は{$pointCountMax}点までです（現在 {$itemCount}点）。"])
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
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
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
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
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
        if ($response = $this->ensureChallengeChoiceSelected($race)) {
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
        $horseNameByNo = $race->horses()
            ->pluck('horse_name', 'horse_no')
            ->mapWithKeys(fn ($name, $no) => [(string) $no => (string) $name])
            ->all();

        return view($conf['view'], [
            'race' => $race,
            'betType' => $betType,
            'mode' => $mode,
            'horseNos' => $horseNos,
            'horseNameByNo' => $horseNameByNo,
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
        $cartItems = collect($cart['items'] ?? [])
            ->map(function (array $row) {
                $betType = (string) ($row['bet_type'] ?? '');
                $selectionKey = (string) ($row['selection_key'] ?? '');

                return [
                    'key' => $this->itemKey($betType, $selectionKey),
                    'bet_type' => $betType,
                    'selection_key' => $selectionKey,
                    'amount' => (int) ($row['amount'] ?? 0),
                ];
            })
            ->filter(fn (array $row) => $row['bet_type'] !== '' && $row['selection_key'] !== '')
            ->values();

        $itemsByKey = $cartItems->keyBy('key');

        $groups = collect($cart['groups'] ?? [])
            ->map(function (array $group) use ($itemsByKey) {
                $itemKeys = collect($group['item_keys'] ?? [])
                    ->map(fn ($key) => (string) $key)
                    ->filter()
                    ->unique()
                    ->values();

                // 追加時に保持した item_keys を使って、現在カートの実体に追従させる。
                // これにより、一部削除後でも point_count / total_amount がズレない。
                if ($itemKeys->isNotEmpty()) {
                    $groupItems = $itemKeys
                        ->map(fn (string $key) => $itemsByKey->get($key))
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        'bet_type' => (string) ($group['bet_type'] ?? ''),
                        'mode' => (string) ($group['mode'] ?? ''),
                        'input' => is_array($group['input'] ?? null) ? $group['input'] : [],
                        'point_count' => count($groupItems),
                        'unit_amount' => $this->detectUnitAmount($groupItems),
                        'total_amount' => (int) collect($groupItems)->sum(fn (array $row) => (int) ($row['amount'] ?? 0)),
                    ];
                }

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
            'removed_items' => collect($cart['removed_items'] ?? [])
                ->map(fn (array $row) => [
                    'bet_type' => (string) ($row['bet_type'] ?? ''),
                    'selection_key' => (string) ($row['selection_key'] ?? ''),
                    'amount' => (int) ($row['amount'] ?? 0),
                    'removed_at' => (string) ($row['removed_at'] ?? ''),
                    'removed_by' => (string) ($row['removed_by'] ?? 'selected_remove'),
                ])
                ->filter(fn (array $row) => $row['bet_type'] !== '' && $row['selection_key'] !== '')
                ->values()
                ->all(),
            'amount_changes' => collect($cart['amount_changes'] ?? [])
                ->map(fn (array $row) => [
                    'bet_type' => (string) ($row['bet_type'] ?? ''),
                    'selection_key' => (string) ($row['selection_key'] ?? ''),
                    'old_amount' => (int) ($row['old_amount'] ?? 0),
                    'new_amount' => (int) ($row['new_amount'] ?? 0),
                    'changed_at' => (string) ($row['changed_at'] ?? ''),
                    'changed_by' => (string) ($row['changed_by'] ?? ''),
                ])
                ->filter(fn (array $row) => $row['bet_type'] !== '' && $row['selection_key'] !== '' && $row['old_amount'] !== $row['new_amount'])
                ->values()
                ->all(),
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

    private function buildRemovedItemLog(array $row, string $removedBy): ?array
    {
        $betType = (string) ($row['bet_type'] ?? '');
        $selectionKey = (string) ($row['selection_key'] ?? '');

        if ($betType === '' || $selectionKey === '') {
            return null;
        }

        return [
            'bet_type' => $betType,
            'selection_key' => $selectionKey,
            'amount' => (int) ($row['amount'] ?? 0),
            'removed_at' => now()->toIso8601String(),
            'removed_by' => $removedBy,
        ];
    }
}
