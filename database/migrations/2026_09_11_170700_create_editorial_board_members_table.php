<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('role_title');
            $table->string('affiliation')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['journal_id', 'is_public', 'sort_order']);
            $table->index(['journal_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_board_members');
    }
};
