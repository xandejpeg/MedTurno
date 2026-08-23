<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_memberships', function (Blueprint $table) {
            $table->decimal('shift_amount', 10, 2)->nullable()->after('active'); // valor por plantão do médico
        });

        Schema::table('shift_templates', function (Blueprint $table) {
            $table->decimal('default_amount', 10, 2)->nullable()->after('amount'); // valor padrão do tipo de turno
        });
    }

    public function down(): void
    {
        Schema::table('hospital_memberships', function (Blueprint $table) {
            $table->dropColumn('shift_amount');
        });
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropColumn('default_amount');
        });
    }
};
