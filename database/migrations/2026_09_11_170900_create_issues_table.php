<?php

use App\Enums\IssueStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('volume_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title')->nullable();
            $table->string('status', 32)->default(IssueStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['volume_id', 'number']);
            $table->index(['journal_id', 'status']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
