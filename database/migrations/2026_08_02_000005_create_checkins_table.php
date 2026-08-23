<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // in, out
            $table->timestamp('checked_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('method', 10)->default('manual'); // manual, gps, qrcode
            $table->timestamps();

            $table->index(['shift_id', 'type']);
        });

        Schema::table('hospitals', function (Blueprint $table) {
            $table->decimal('checkin_latitude', 10, 7)->nullable()->after('conflict_mode');
            $table->decimal('checkin_longitude', 10, 7)->nullable()->after('checkin_latitude');
            $table->unsignedSmallInteger('checkin_radius_m')->nullable()->after('checkin_longitude');
            $table->unsignedSmallInteger('checkin_window_before_min')->default(30)->after('checkin_radius_m');
            $table->unsignedSmallInteger('checkout_window_after_min')->default(30)->after('checkin_window_before_min');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['checkin_latitude', 'checkin_longitude', 'checkin_radius_m', 'checkin_window_before_min', 'checkout_window_after_min']);
        });
        Schema::dropIfExists('checkins');
    }
};
