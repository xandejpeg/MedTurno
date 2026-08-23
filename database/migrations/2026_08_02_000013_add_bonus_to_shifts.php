<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('bonus_amount', 10, 2)->default(0)->after('amount');
        });

        Schema::table('hospitals', function (Blueprint $table) {
            $table->decimal('bonus_night', 10, 2)->default(0)->after('checkout_window_after_min');
            $table->decimal('bonus_weekend', 10, 2)->default(0)->after('bonus_night');
            $table->decimal('bonus_oncall', 10, 2)->default(0)->after('bonus_weekend');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('bonus_amount');
        });
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['bonus_night', 'bonus_weekend', 'bonus_oncall']);
        });
    }
};
