<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->string('template')->nullable();
            $table->string('status', 20)->default('enviado');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['channel', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
