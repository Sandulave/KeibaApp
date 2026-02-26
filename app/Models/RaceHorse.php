<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceHorse extends Model
{
    protected $fillable = [
        'race_id',
        'horse_no',
        'horse_name',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
