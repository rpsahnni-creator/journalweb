<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'editor_id')) {
                $table->foreignId('editor_id')->nullable()->after('corresponding_author_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('articles', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('published_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'editor_id')) {
                $table->dropConstrainedForeignId('editor_id');
            }

            if (Schema::hasColumn('articles', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
        });
    }
};
