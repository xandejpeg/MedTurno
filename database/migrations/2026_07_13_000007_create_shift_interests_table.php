<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pendente');
            $table->timestamps();

            $table->unique(['shift_id', 'user_id']);
            $table->index(['shift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_interests');
    }
};
