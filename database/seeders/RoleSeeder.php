<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, description: string}>
     */
    public const ROLES = [
        [
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system access for development and journal operations.',
        ],
        [
            'name' => 'Journal Manager',
            'slug' => 'journal_manager',
            'description' => 'Manages journal settings, issues, and editorial operations.',
        ],
        [
            'name' => 'Editor-in-Chief',
            'slug' => 'editor_in_chief',
            'description' => 'Leads editorial policy, decisions, and publication.',
        ],
        [
            'name' => 'Editor',
            'slug' => 'editor',
            'description' => 'Manages peer review and editorial decisions.',
        ],
        [
            'name' => 'Section Editor',
            'slug' => 'section_editor',
            'description' => 'Handles review and decisions within an assigned section.',
        ],
        [
            'name' => 'Copyeditor',
            'slug' => 'copyeditor',
            'description' => 'Prepares accepted manuscripts for publication.',
        ],
        [
            'name' => 'Reviewer',
            'slug' => 'reviewer',
            'description' => 'Provides peer review reports on assigned manuscripts.',
        ],
        [
            'name' => 'Author',
            'slug' => 'author',
            'description' => 'Submits manuscripts and manages revisions.',
        ],
        [
            'name' => 'Reader',
            'slug' => 'reader',
            'description' => 'Registered reader of published journal content.',
        ],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
