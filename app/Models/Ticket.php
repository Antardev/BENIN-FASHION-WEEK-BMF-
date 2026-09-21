<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $guarded = [];

    protected $casts = ['checked_in_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
