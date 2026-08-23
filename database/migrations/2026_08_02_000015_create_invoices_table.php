<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('rascunho'); // rascunho, emitida, cancelada
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
