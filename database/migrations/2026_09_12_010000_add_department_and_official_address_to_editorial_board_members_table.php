<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_board_members', function (Blueprint $table) {
            $table->string('department')->nullable()->after('role_title');
            $table->text('official_address')->nullable()->after('affiliation');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_board_members', function (Blueprint $table) {
            $table->dropColumn(['department', 'official_address']);
        });
    }
};
