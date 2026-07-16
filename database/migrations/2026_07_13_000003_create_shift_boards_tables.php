<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['hospital_id', 'name']);
        });

        Schema::create('shift_board_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shift_board_id', 'user_id']);
        });

        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_board_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=dom … 6=sáb
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->unsignedTinyInteger('slots')->default(1);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('label')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['shift_board_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_templates');
        Schema::dropIfExists('shift_board_memberships');
        Schema::dropIfExists('shift_boards');
    }
};
