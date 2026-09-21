<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $guarded = [];

    protected $casts = ['starts_at' => 'datetime', 'is_active' => 'boolean', 'images' => 'array'];

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('price');
    }
}
