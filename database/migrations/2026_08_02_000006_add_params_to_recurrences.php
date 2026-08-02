<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurrences', function (Blueprint $table) {
            $table->unsignedTinyInteger('day_of_month')->nullable()->after('type'); // 1-31
            $table->unsignedTinyInteger('interval_days')->nullable()->after('day_of_month'); // a cada N dias
            $table->unsignedTinyInteger('week_of_month')->nullable()->after('interval_days'); // 1-5
        });
    }

    public function down(): void
    {
        Schema::table('recurrences', function (Blueprint $table) {
            $table->dropColumn(['day_of_month', 'interval_days', 'week_of_month']);
        });
    }
};
