<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'defile-haute-couture-distinctions')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Front Row')
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
            ->where('slug', 'defile-haute-couture-distinctions')
            ->value('id');

        if (! $eventId || DB::table('ticket_types')->where('event_id', $eventId)->where('name', 'Front Row')->exists()) {
            return;
        }

        DB::table('ticket_types')->insert([
            'event_id' => $eventId,
            'name' => 'Front Row',
            'description' => 'Premier rang du podium',
            'price' => 50000,
            'capacity' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
