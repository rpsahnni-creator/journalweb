<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->text('author_response')->nullable()->after('conflict_of_interest_statement');
            $table->timestamp('revision_due_at')->nullable()->after('submitted_at');
        });

        Schema::table('revisions', function (Blueprint $table) {
            $table->text('author_response')->nullable()->after('notes_to_editor');
        });

        Schema::table('editorial_decisions', function (Blueprint $table) {
            $table->timestamp('revision_due_at')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_decisions', function (Blueprint $table) {
            $table->dropColumn('revision_due_at');
        });

        Schema::table('revisions', function (Blueprint $table) {
            $table->dropColumn('author_response');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['author_response', 'revision_due_at']);
        });
    }
};
