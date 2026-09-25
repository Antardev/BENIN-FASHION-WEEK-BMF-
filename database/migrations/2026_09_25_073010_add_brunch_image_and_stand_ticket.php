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

        DB::table('events')
            ->where('id', $eventId)
            ->update([
                'images' => json_encode(['Fashionbrunch.jpeg']),
                'updated_at' => now(),
            ]);

        if (! DB::table('ticket_types')->where('event_id', $eventId)->where('name', 'Reservation de stand')->exists()) {
            DB::table('ticket_types')->insert([
                'event_id' => $eventId,
                'name' => 'Reservation de stand',
                'description' => 'Stand 3m x 3m avec table et 2 chaises',
                'price' => 50000,
                'capacity' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'fashion-brunch')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('events')
            ->where('id', $eventId)
            ->where('images', json_encode(['BFW Brunch Stand.jpg.jpeg']))
            ->update([
                'images' => json_encode([]),
                'updated_at' => now(),
            ]);

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Reservation de stand')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.ticket_type_id', 'ticket_types.id');
            })
            ->delete();
    }
};
