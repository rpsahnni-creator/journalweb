<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, description: string}>
     */
    public const PERMISSIONS = [
        ['name' => 'Manage journals', 'slug' => 'journals.manage', 'description' => 'Create and update journal records.'],
        ['name' => 'Update journal settings', 'slug' => 'journals.settings.update', 'description' => 'Change journal configuration values.'],
        ['name' => 'Manage users', 'slug' => 'users.manage', 'description' => 'Create and update user accounts.'],
        ['name' => 'Manage roles', 'slug' => 'roles.manage', 'description' => 'Assign roles and permissions.'],
        ['name' => 'Manage editorial board', 'slug' => 'editorial_board.manage', 'description' => 'Maintain editorial board listings.'],
        ['name' => 'View all articles', 'slug' => 'articles.view_all', 'description' => 'View unpublished manuscripts in editorial workflows.'],
        ['name' => 'Submit articles', 'slug' => 'articles.submit', 'description' => 'Submit new manuscripts.'],
        ['name' => 'Edit own articles', 'slug' => 'articles.edit_own', 'description' => 'Edit manuscripts the user authors.'],
        ['name' => 'Review articles', 'slug' => 'articles.review', 'description' => 'Access manuscripts assigned for review.'],
        ['name' => 'Make editorial decisions', 'slug' => 'articles.decide', 'description' => 'Record editorial decisions on manuscripts.'],
        ['name' => 'Publish articles', 'slug' => 'articles.publish', 'description' => 'Schedule and publish accepted articles.'],
        ['name' => 'Manage volumes', 'slug' => 'volumes.manage', 'description' => 'Create and update volumes.'],
        ['name' => 'Manage issues', 'slug' => 'issues.manage', 'description' => 'Create and update issues.'],
        ['name' => 'Assign reviewers', 'slug' => 'reviews.assign', 'description' => 'Invite and manage reviewers.'],
        ['name' => 'Submit reviews', 'slug' => 'reviews.submit', 'description' => 'Submit peer review reports.'],
        ['name' => 'View unpublished files', 'slug' => 'files.view_unpublished', 'description' => 'Download private manuscript files.'],
        ['name' => 'View audit logs', 'slug' => 'audit.view', 'description' => 'Inspect audit history.'],
        ['name' => 'Manage policies', 'slug' => 'policies.manage', 'description' => 'Create and publish journal policies.'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
