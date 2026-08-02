<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('municipality')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'name']);
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('hospital_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
        Schema::dropIfExists('units');
    }
};
