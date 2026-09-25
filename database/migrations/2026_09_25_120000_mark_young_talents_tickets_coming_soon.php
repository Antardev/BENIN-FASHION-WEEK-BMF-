<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticket_types', 'is_available')) {
            Schema::table('ticket_types', function ($table) {
                $table->boolean('is_available')->default(true)->after('capacity');
            });
        }

        $eventId = DB::table('events')->where('slug', 'concours-jeunes-talents')->value('id');

        if ($eventId) {
            DB::table('ticket_types')->where('event_id', $eventId)->update(['is_available' => false, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ticket_types', 'is_available')) {
            Schema::table('ticket_types', function ($table) {
                $table->dropColumn('is_available');
            });
        }
    }
};
