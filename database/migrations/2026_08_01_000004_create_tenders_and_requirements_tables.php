<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('orgao')->nullable();
            $table->string('numero')->nullable();
            $table->string('status', 30)->default('analise'); // analise, aplicando, em_andamento, concluida, descartada
            $table->unsignedSmallInteger('progress')->default(0); // 0-100
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('tender_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('faltando'); // faltando, parcial, pronto, na_aplicacao
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['tender_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_requirements');
        Schema::dropIfExists('tenders');
    }
};
