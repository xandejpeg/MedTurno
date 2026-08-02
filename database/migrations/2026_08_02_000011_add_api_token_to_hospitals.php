<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('api_token', 64)->nullable()->unique()->after('checkout_window_after_min');
        });

        foreach (\App\Models\Hospital::whereNull('api_token')->get() as $hospital) {
            $hospital->forceFill(['api_token' => Str::random(48)])->save();
        }
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
