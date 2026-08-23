<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->nullable();
            $table->string('scope', 20)->default('hospital'); // hospital, all
            $table->timestamps();

            $table->index(['user_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
