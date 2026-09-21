<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'fashion-brunch')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Table VIP')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.ticket_type_id', 'ticket_types.id');
            })
            ->delete();
    }

    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'fashion-brunch')
            ->value('id');

        if (! $eventId || DB::table('ticket_types')->where('event_id', $eventId)->where('name', 'Table VIP')->exists()) {
            return;
        }

        DB::table('ticket_types')->insert([
            'event_id' => $eventId,
            'name' => 'Table VIP',
            'description' => 'Table réservée pour 4 personnes',
            'price' => 50000,
            'capacity' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
