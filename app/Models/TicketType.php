<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    protected $guarded = [];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Places prises : commandes payées + commandes en attente depuis moins de 15 min. */
    public function sold(): int
    {
        return (int) $this->orders()
            ->where(fn ($q) => $q->where('status', 'paid')
                ->orWhere(fn ($q) => $q->where('status', 'pending')
                    ->where('created_at', '>', now()->subMinutes(15))))
            ->sum('quantity');
    }

    public function remaining(): int
    {
        return max(0, $this->capacity - $this->sold());
    }

    public function getPriceLabelAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ').' FCFA';
    }
}
