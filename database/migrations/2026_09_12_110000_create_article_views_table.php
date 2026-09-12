<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['page_view', 'pdf_download'])->default('page_view');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['article_id', 'type']);
            $table->index(['article_id', 'viewed_at']);
        });

        // Add cached counters to articles table for efficient display.
        Schema::table('articles', function (Blueprint $table): void {
            $table->unsignedInteger('view_count')->default(0)->after('published_at');
            $table->unsignedInteger('download_count')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['view_count', 'download_count']);
        });
        Schema::dropIfExists('article_views');
    }
};
