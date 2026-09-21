<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = [];

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getTotalLabelAttribute(): string
    {
        return number_format($this->total, 0, ',', ' ').' FCFA';
    }

    /** Marque la commande payée et génère un billet par place (idempotent). */
    public function markPaid(?string $paymentRef = null): void
    {
        if ($this->status === 'paid') {
            return;
        }
        $this->update(['status' => 'paid', 'payment_ref' => $paymentRef]);
        for ($i = 0; $i < $this->quantity; $i++) {
            $this->tickets()->create(['code' => 'BFW-'.strtoupper(Str::random(10))]);
        }
    }
}
