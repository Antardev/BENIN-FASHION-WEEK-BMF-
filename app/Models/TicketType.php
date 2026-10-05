<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    protected $guarded = [];

    protected $casts = ['is_available' => 'boolean'];

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

    /** Un billet n'est « à venir » que si is_available vaut explicitement false en base. */
    public function isComingSoon(): bool
    {
        return $this->is_available === false;
    }

    public function getPriceLabelAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ').' FCFA';
    }

    /**
     * Chemin absolu du visuel du billet (public/images/Tickets), ou null.
     * Source unique utilisée par l'email ET le PDF (la colonne « image » n'existe pas en base).
     */
    public function ticketImagePath(): ?string
    {
        $this->loadMissing('event');

        $image = match ([$this->event->slug, $this->name]) {
            ['defile-haute-couture-distinctions', 'VIP'] => 'defile-vip.jpeg',
            ['defile-haute-couture-distinctions', 'Standard'] => 'defile-standard.jpeg',
            ['fashion-brunch', 'Place brunch'] => 'brunch-place.jpeg',
            ['fashion-brunch', 'Reservation de stand'] => 'brunch-stand.png',
            ['concours-jeunes-talents', 'VIP'] => 'CJT10K.jpeg',
            ['concours-jeunes-talents', 'Standard'] => 'CJT.jpeg',
            default => null,
        };

        $path = $image ? public_path('images/Tickets/'.$image) : null;

        return $path && is_file($path) ? $path : null;
    }
}
