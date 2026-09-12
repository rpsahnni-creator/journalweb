<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('editor_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 32);
            $table->text('comments_to_author')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['article_id', 'decision']);
            $table->index(['editor_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_decisions');
    }
};
