<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=domingo, 6=sábado
            $table->string('period', 10); // dia, noite, all
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['hospital_id', 'weekday', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_blocks');
    }
};
