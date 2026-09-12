<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('academic_title', 50)->nullable()->after('name');
            $table->string('affiliation')->nullable()->after('academic_title');
            $table->string('orcid', 19)->nullable()->unique()->after('affiliation');
            $table->text('biography')->nullable()->after('orcid');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropUnique(['orcid']);
            $table->dropColumn([
                'academic_title',
                'affiliation',
                'orcid',
                'biography',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
