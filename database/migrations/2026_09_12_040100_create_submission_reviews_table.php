<?php

use App\Enums\SubmissionReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('recommendation', 32)->nullable();
            $table->text('comments_to_editor')->nullable();
            $table->text('comments_to_author')->nullable();
            $table->string('status', 32)->default(SubmissionReviewStatus::Assigned->value);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'reviewer_id']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['submission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_reviews');
    }
};
