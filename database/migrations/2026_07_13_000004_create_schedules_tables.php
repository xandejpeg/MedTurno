<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_template_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // semanal | quinzenal
            $table->date('reference_date');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['shift_template_id', 'active']);
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_board_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->string('status')->default('rascunho');
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['shift_board_id', 'year', 'month']);
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_board_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('sem_medico');
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('note')->nullable();
            $table->string('origin')->default('manual'); // manual | recorrencia
            $table->foreignId('recurrence_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
            $table->index(['schedule_id', 'date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('recurrences');
    }
};
