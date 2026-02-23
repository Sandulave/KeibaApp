<?php

namespace App\Services\Bet;

class CartService
{
    private function cartKey(int $raceId): string
    {
        return "bet_cart.{$raceId}";
    }

    public function addItems(int $raceId, array $items): void
    {
        $cartKey = $this->cartKey($raceId);

        $cart = session($cartKey, [
            'race_id' => $raceId,
            'items' => [],
        ]);

        // 既存をindex化
        $index = [];
        foreach ($cart['items'] as $i => $row) {
            $key = $row['bet_type'] . '|' . $row['selection_key'];
            $index[$key] = $i;
        }

        foreach ($items as $new) {
            $key = $new['bet_type'] . '|' . $new['selection_key'];

            if (isset($index[$key])) {
                $cart['items'][$index[$key]]['amount'] += (int)$new['amount'];
            } else {
                $cart['items'][] = $new;
                $index[$key] = count($cart['items']) - 1;
            }
        }

        session([$cartKey => $cart]);
    }
}
