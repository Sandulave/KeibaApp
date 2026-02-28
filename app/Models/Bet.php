<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'race_id',
        'idempotency_key',
        'bought_at',
        'memo',
        'stake_amount',
        'return_amount',
        'hit_count',
        'roi_percent',
        'settled_at',
        'build_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'bought_at' => 'datetime',
            'settled_at' => 'datetime',
            'roi_percent' => 'decimal:2',
            'build_snapshot' => 'array',
            'idempotency_key' => 'string',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($bet) {
            if (empty($bet->bought_at)) {
                $bet->bought_at = now();
            }
        });
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BetItem::class);
    }
}
