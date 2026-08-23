<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name'); // apelido
            $table->string('cbo')->nullable()->after('specialty'); // ocupação (CBO)
            $table->string('council_type')->nullable()->after('cbo'); // tipo de conselho (CRM, COREN, CRO)
            $table->string('internal_id')->nullable()->after('council_type'); // matrícula/ID interno
            $table->date('hired_at')->nullable()->after('internal_id'); // data de ingresso
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nickname', 'cbo', 'council_type', 'internal_id', 'hired_at']);
        });
    }
};
