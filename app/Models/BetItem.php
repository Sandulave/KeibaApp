<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetItem extends Model
{
    protected $fillable = ['bet_id', 'bet_type', 'selection_key', 'amount', 'return_amount', 'is_hit'];

    protected function casts(): array
    {
        return [
            'is_hit' => 'boolean',
        ];
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
