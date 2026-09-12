<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->boolean('is_special_issue')->default(false)->after('is_current');
            $table->string('special_issue_theme')->nullable()->after('is_special_issue');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn(['is_special_issue', 'special_issue_theme']);
        });
    }
};
