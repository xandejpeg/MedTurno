<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('type')->default('individual')->after('hospital_id');
            $table->foreignId('shift_board_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->string('plain_token')->nullable()->after('token_hash');
            $table->string('email')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->timestamp('expires_at')->nullable()->change();
        });

        Schema::table('hospital_memberships', function (Blueprint $table) {
            $table->foreignId('invitation_id')->nullable()->after('hospital_id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf')->nullable()->after('phone');
            $table->string('photo_path')->nullable()->after('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'photo_path']);
        });

        Schema::table('hospital_memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invitation_id');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_board_id');
            $table->dropColumn(['type', 'plain_token']);
        });
    }
};
