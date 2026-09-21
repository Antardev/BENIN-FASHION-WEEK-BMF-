<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'concours-jeunes-talents')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Entrée publique')
            ->update(['name' => 'Standard', 'updated_at' => now()]);

        if (! DB::table('ticket_types')->where('event_id', $eventId)->where('name', 'VIP')->exists()) {
            DB::table('ticket_types')->insert([
                'event_id' => $eventId,
                'name' => 'VIP',
                'description' => 'Accès privilégié et placement réservé',
                'price' => 10000,
                'capacity' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $eventId = DB::table('events')
            ->where('slug', 'concours-jeunes-talents')
            ->value('id');

        if (! $eventId) {
            return;
        }

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'VIP')
            ->delete();

        DB::table('ticket_types')
            ->where('event_id', $eventId)
            ->where('name', 'Standard')
            ->update(['name' => 'Entrée publique', 'updated_at' => now()]);
    }
};
