<?php

use App\Enums\ArticleFileType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 32)->default(ArticleFileType::Manuscript->value);
            $table->string('original_filename');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['article_id', 'type']);
            $table->index(['article_id', 'is_public']);
            $table->index('revision_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_files');
    }
};
