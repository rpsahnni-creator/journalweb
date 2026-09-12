<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedInteger('volume_number')->nullable()->after('volume_id');
            $table->string('publication_month_year')->nullable()->after('title');
            $table->boolean('is_current')->default(false)->after('published_at');
            $table->index('is_current');
            $table->index(['journal_id', 'is_current']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('issue_id')->nullable()->after('journal_id')->constrained()->nullOnDelete();
            $table->string('pdf_path')->nullable()->after('doi');
            $table->string('pdf_original_filename')->nullable()->after('pdf_path');
            $table->index('issue_id');
        });

        if (Schema::hasTable('volumes')) {
            $issues = DB::table('issues')->whereNull('volume_number')->get(['id', 'volume_id']);

            foreach ($issues as $issue) {
                $volumeNumber = DB::table('volumes')->where('id', $issue->volume_id)->value('number');

                if ($volumeNumber !== null) {
                    DB::table('issues')->where('id', $issue->id)->update([
                        'volume_number' => $volumeNumber,
                    ]);
                }
            }
        }

        if (Schema::hasTable('issue_articles')) {
            $placements = DB::table('issue_articles')
                ->orderByDesc('id')
                ->get(['article_id', 'issue_id']);

            foreach ($placements as $placement) {
                DB::table('articles')
                    ->where('id', $placement->article_id)
                    ->whereNull('issue_id')
                    ->update(['issue_id' => $placement->issue_id]);
            }
        }

        $currentIssueId = DB::table('issues')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->value('id');

        if ($currentIssueId) {
            DB::table('issues')->where('id', $currentIssueId)->update(['is_current' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issue_id');
            $table->dropColumn(['pdf_path', 'pdf_original_filename']);
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex(['is_current']);
            $table->dropIndex(['journal_id', 'is_current']);
            $table->dropColumn(['volume_number', 'publication_month_year', 'is_current']);
        });
    }
};
