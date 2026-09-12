<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('affiliation')->nullable();
            $table->string('orcid', 19)->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->boolean('is_corresponding')->default(false);
            $table->timestamps();

            $table->unique(['article_id', 'sequence']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_authors');
    }
};
