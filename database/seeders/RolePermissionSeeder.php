<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>|string>
     */
    private const MAP = [
        'admin' => '*',
        'journal_manager' => [
            'journals.settings.update',
            'editorial_board.manage',
            'articles.view_all',
            'volumes.manage',
            'issues.manage',
            'reviews.assign',
            'files.view_unpublished',
            'policies.manage',
        ],
        'editor_in_chief' => [
            'journals.settings.update',
            'editorial_board.manage',
            'articles.view_all',
            'articles.decide',
            'articles.publish',
            'volumes.manage',
            'issues.manage',
            'reviews.assign',
            'files.view_unpublished',
            'audit.view',
            'policies.manage',
        ],
        'editor' => [
            'articles.view_all',
            'articles.decide',
            'volumes.manage',
            'issues.manage',
            'reviews.assign',
            'files.view_unpublished',
        ],
        'section_editor' => [
            'articles.view_all',
            'articles.decide',
            'reviews.assign',
            'files.view_unpublished',
        ],
        'copyeditor' => [
            'articles.view_all',
            'articles.publish',
            'files.view_unpublished',
        ],
        'reviewer' => [
            'articles.review',
            'reviews.submit',
        ],
        'author' => [
            'articles.submit',
            'articles.edit_own',
        ],
        'reader' => [],
    ];

    public function run(): void
    {
        $permissions = Permission::query()->pluck('id', 'slug');

        foreach (self::MAP as $roleSlug => $permissionSlugs) {
            $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

            $ids = $permissionSlugs === '*'
                ? $permissions->values()->all()
                : $permissions->only($permissionSlugs)->values()->all();

            $role->permissions()->sync($ids);
        }
    }
}
