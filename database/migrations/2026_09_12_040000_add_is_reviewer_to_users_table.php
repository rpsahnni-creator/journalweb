<?php

use App\Enums\RoleSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_reviewer')->default(false)->after('is_editor');
            $table->index('is_reviewer');
        });

        $reviewerRoleId = DB::table('roles')->where('slug', RoleSlug::Reviewer->value)->value('id');

        if ($reviewerRoleId) {
            $userIds = DB::table('role_user')
                ->where('role_id', $reviewerRoleId)
                ->pluck('user_id')
                ->unique();

            if ($userIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $userIds)->update(['is_reviewer' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_reviewer']);
            $table->dropColumn('is_reviewer');
        });
    }
};
