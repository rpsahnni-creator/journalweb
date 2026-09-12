<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('article_type', 64)->nullable()->after('keywords');
            $table->text('cover_letter')->nullable()->after('language');
            $table->boolean('originality_confirmed')->default(false)->after('cover_letter');
            $table->boolean('conflict_of_interest_declared')->default(false)->after('originality_confirmed');
            $table->text('conflict_of_interest_statement')->nullable()->after('conflict_of_interest_declared');
        });

        Schema::create('article_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['article_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_status_events');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'article_type',
                'cover_letter',
                'originality_confirmed',
                'conflict_of_interest_declared',
                'conflict_of_interest_statement',
            ]);
        });
    }
};
