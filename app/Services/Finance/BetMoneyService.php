<?php

namespace App\Services\Finance;

class BetMoneyService
{
    public const NORMAL_ALLOWANCE = 10_000;
    public const CHALLENGE_ALLOWANCE = 30_000;

    public function allowanceForChoice(?string $choice): int
    {
        return match ($choice) {
            'challenge' => self::CHALLENGE_ALLOWANCE,
            'normal' => self::NORMAL_ALLOWANCE,
            default => 0,
        };
    }

    public function challengeChoiceDelta(?string $oldChoice, ?string $newChoice): int
    {
        return $this->allowanceForChoice($newChoice) - $this->allowanceForChoice($oldChoice);
    }

    public function roiPercent(int $stakeAmount, int $returnAmount): ?float
    {
        if ($stakeAmount <= 0) {
            return null;
        }

        return round(($returnAmount / $stakeAmount) * 100, 2);
    }

    public function profitAmount(int $stakeAmount, int $returnAmount, int $bonusPoints = 0): int
    {
        return $returnAmount - $stakeAmount + $bonusPoints;
    }
}

