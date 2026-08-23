<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('swap_requires_approval')->default(true)->after('status');
        });

        // Escalas criadas até agora (Alex Gestor / Thallys) exigem aprovação do gestor.
        DB::table('schedules')->update(['swap_requires_approval' => true]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('swap_requires_approval');
        });
    }
};
