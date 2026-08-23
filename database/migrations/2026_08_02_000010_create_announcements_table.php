<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->index(['hospital_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
