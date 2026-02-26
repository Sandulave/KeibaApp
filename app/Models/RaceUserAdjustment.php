<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceUserAdjustment extends Model
{
    protected $fillable = [
        'user_id',
        'race_id',
        'bonus_points',
        'challenge_choice',
        'challenge_chosen_at',
        'note',
    ];

    protected $casts = [
        'challenge_chosen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
