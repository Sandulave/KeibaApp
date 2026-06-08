<?php

namespace App\Services\Finance;

use App\Models\Race;

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

    public function allowanceForRaceChoice(Race $race, ?string $choice): int
    {
        if (config('domain.site.type') === 'summer') {
            return $this->summerAllowanceForRace($race);
        }

        return match ($choice) {
            'challenge' => (int) ($race->challenge_allowance ?? self::CHALLENGE_ALLOWANCE),
            'normal' => (int) ($race->normal_allowance ?? self::NORMAL_ALLOWANCE),
            default => 0,
        };
    }

    public function summerAllowanceForRace(Race $race): int
    {
        $raceName = (string) $race->name;

        if (preg_match('/(?:G2|Ｇ2|GⅡ|ＧⅡ|GII|ＧＩＩ)/u', $raceName) === 1) {
            return (int) config('domain.site.summer_allowances.g2', 5_000);
        }

        if (preg_match('/(?:G3|Ｇ3|GⅢ|ＧⅢ|GIII|ＧＩＩＩ)/u', $raceName) === 1) {
            return (int) config('domain.site.summer_allowances.g3', 3_000);
        }

        return 0;
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
