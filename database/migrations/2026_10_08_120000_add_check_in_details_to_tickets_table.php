<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traçabilité du contrôle d'entrée : qui a scanné le billet, depuis quel appareil.
 * L'heure du scan est déjà portée par `checked_in_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->foreignId('checked_in_by')->nullable()->after('checked_in_at')
                ->constrained('users')->nullOnDelete();
            $t->string('check_in_ip', 45)->nullable()->after('checked_in_by');
            $t->string('check_in_device')->nullable()->after('check_in_ip');
            $t->index('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->dropIndex(['checked_in_at']);
            $t->dropConstrainedForeignId('checked_in_by');
            $t->dropColumn(['check_in_ip', 'check_in_device']);
        });
    }
};
