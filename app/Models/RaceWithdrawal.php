<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceWithdrawal extends Model
{
    protected $table = 'race_withdrawals';

    protected $fillable = ['race_id', 'horse_no', 'reason'];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
