<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')
            ->where('slug', 'defile-haute-couture-distinctions')
            ->update([
                'starts_at' => '2026-10-23 00:00:00',
                'venue' => "FRANCOISE'S GARDEN COTONOU",
                'updated_at' => now(),
            ]);

        DB::table('events')
            ->where('slug', 'fashion-brunch')
            ->update([
                'starts_at' => '2026-10-24 00:00:00',
                'venue' => 'AMBA YARD FERME (TORI)',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('events')
            ->whereIn('slug', ['defile-haute-couture-distinctions', 'fashion-brunch'])
            ->update([
                'starts_at' => null,
                'venue' => null,
                'updated_at' => now(),
            ]);
    }
};
