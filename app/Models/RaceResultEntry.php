<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceResultEntry extends Model
{
    protected $table = 'race_results';

    protected $fillable = ['race_id', 'rank', 'horse_no'];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
