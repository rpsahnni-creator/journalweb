<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('articles', 'is_demo')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('articles', 'is_demo')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
