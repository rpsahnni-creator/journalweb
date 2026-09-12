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
            $table->boolean('is_editor')->default(false)->after('is_active');
            $table->index('is_editor');
        });

        $editorRoleIds = DB::table('roles')
            ->whereIn('slug', RoleSlug::editorialValues())
            ->pluck('id');

        if ($editorRoleIds->isNotEmpty()) {
            $userIds = DB::table('role_user')
                ->whereIn('role_id', $editorRoleIds)
                ->pluck('user_id')
                ->unique();

            if ($userIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $userIds)->update(['is_editor' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_editor']);
            $table->dropColumn('is_editor');
        });
    }
};
