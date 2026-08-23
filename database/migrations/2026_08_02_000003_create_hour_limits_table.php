<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('hours'); // carga limite
            $table->string('period', 10)->default('monthly'); // monthly, weekly
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('on_swap', 10)->default('alert'); // block, alert
            $table->string('on_announce', 10)->default('alert'); // block, alert
            $table->timestamps();

            $table->index(['user_id', 'hospital_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_limits');
    }
};
