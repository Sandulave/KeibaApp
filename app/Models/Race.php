<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Race extends Model
{
    protected $fillable = [
        'name',
        'horse_count',
        'race_date',
        'course',
        'is_betting_closed',
    ];

    protected $casts = [
        'is_betting_closed' => 'boolean',
    ];

    // result, resultsリレーション削除

    public function payouts(): HasMany
    {
        return $this->hasMany(RacePayout::class);
    }

    public function resultEntries(): HasMany
    {
        return $this->hasMany(RaceResultEntry::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(RaceWithdrawal::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }
}
