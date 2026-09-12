<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });

        Schema::table('issue_articles', function (Blueprint $table) {
            $table->string('article_number', 64)->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('issue_articles', function (Blueprint $table) {
            $table->dropColumn('article_number');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
