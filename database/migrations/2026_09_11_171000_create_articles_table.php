<?php

use App\Enums\ArticleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corresponding_author_id')->constrained('users')->restrictOnDelete();
            $table->string('submission_number', 32)->unique();
            $table->string('title');
            $table->string('slug');
            $table->text('abstract')->nullable();
            $table->json('keywords')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('status', 32)->default(ArticleStatus::Draft->value);
            $table->string('doi', 128)->nullable()->unique();
            $table->unsignedInteger('page_start')->nullable();
            $table->unsignedInteger('page_end')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['journal_id', 'slug']);
            $table->index(['journal_id', 'status']);
            $table->index('corresponding_author_id');
            $table->index('submitted_at');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
