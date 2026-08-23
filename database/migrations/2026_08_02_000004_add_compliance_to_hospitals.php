<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_shift_hours')->nullable()->after('default_shift_amount');
            $table->unsignedSmallInteger('min_rest_hours')->nullable()->after('max_shift_hours');
            $table->unsignedSmallInteger('min_rest_hours_night')->nullable()->after('min_rest_hours');
            $table->string('conflict_mode', 10)->default('alert')->after('min_rest_hours_night'); // alert, block, off
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['max_shift_hours', 'min_rest_hours', 'min_rest_hours_night', 'conflict_mode']);
        });
    }
};
