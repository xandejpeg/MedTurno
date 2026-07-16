<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('hospital_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'hospital_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_memberships');
        Schema::dropIfExists('hospitals');
    }
};
