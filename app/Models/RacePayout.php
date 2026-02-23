<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacePayout extends Model
{
    protected $fillable = [
        'race_id',
        'bet_type',
        'selection_scope',
        'selection_key',
        'payout_per_100',
        'popularity',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
