<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'review_round')) {
                $table->unsignedInteger('review_round')->default(1);
            }
        });

        Schema::table('submission_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('submission_reviews', 'round')) {
                $table->unsignedInteger('round')->default(1);
            }

            $table->index(['submission_id', 'round']);
        });

        $this->dropReviewerUnique();
    }

    public function down(): void
    {
        Schema::table('submission_reviews', function (Blueprint $table) {
            $table->dropIndex(['submission_id', 'round']);

            if (Schema::hasColumn('submission_reviews', 'round')) {
                $table->dropColumn('round');
            }

            $table->unique(['submission_id', 'reviewer_id']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'review_round')) {
                $table->dropColumn('review_round');
            }
        });
    }

    private function dropReviewerUnique(): void
    {
        $indexes = Schema::getIndexes('submission_reviews');

        foreach ($indexes as $index) {
            $columns = $index['columns'] ?? [];
            $unique = (bool) ($index['unique'] ?? false);

            if ($unique && $columns === ['submission_id', 'reviewer_id']) {
                Schema::table('submission_reviews', function (Blueprint $table) use ($index): void {
                    $table->dropUnique($index['name']);
                });

                return;
            }
        }
    }
};
