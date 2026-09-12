<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->string('response_note', 1000)->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('reviewer_assignments', function (Blueprint $table) {
            $table->dropColumn('response_note');
        });
    }
};
