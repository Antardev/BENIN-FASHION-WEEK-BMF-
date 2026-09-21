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
            ->where('name', 'Standard')
            ->update([
                'price' => 15000,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'defile-haute-couture-distinctions')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Standard')
            ->update([
                'price' => 10000,
                'updated_at' => now(),
            ]);
    }
};
