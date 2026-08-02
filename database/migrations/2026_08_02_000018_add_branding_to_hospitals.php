<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('brand_color', 20)->nullable()->after('bonus_oncall'); // cor principal (hex)
            $table->string('brand_logo_path')->nullable()->after('brand_color'); // caminho do logo
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn(['brand_color', 'brand_logo_path']);
        });
    }
};
