<?php

namespace Tests\Feature\Security;

use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use App\Support\ProductionSafety;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_logs_a_warning_when_the_default_admin_password_remains(): void
    {
        $this->app['env'] = 'local';
        $this->seed(DatabaseSeeder::class);
        Event::fake([MessageLogged::class]);

        $this->app['env'] = 'production';

        $this->assertTrue(ProductionSafety::warnIfDefaultAdminExists());
        Event::assertDispatched(
            MessageLogged::class,
            fn (MessageLogged $event): bool => $event->level === 'warning'
                && str_contains((string) $event->message, 'admin@example.com')
                && str_contains((string) $event->message, 'default password')
        );

        $this->assertDatabaseHas('users', ['email' => AdminUserSeeder::EMAIL]);
        $this->assertTrue(Hash::check(AdminUserSeeder::PASSWORD, User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail()->password));
    }

    public function test_production_does_not_warn_after_the_default_admin_password_is_changed(): void
    {
        $this->app['env'] = 'local';
        $this->seed(DatabaseSeeder::class);
        Event::fake([MessageLogged::class]);

        $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->firstOrFail();
        $admin->forceFill(['password' => 'ChangedPassword1'])->save();

        $this->app['env'] = 'production';

        $this->assertFalse(ProductionSafety::warnIfDefaultAdminExists());
        Event::assertNotDispatched(MessageLogged::class);
        $this->assertDatabaseHas('users', ['email' => AdminUserSeeder::EMAIL]);
    }

    public function test_production_warns_when_debug_mode_is_left_on(): void
    {
        Event::fake([MessageLogged::class]);
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        $this->assertTrue(ProductionSafety::warnIfDebugEnabled());
        Event::assertDispatched(
            MessageLogged::class,
            fn (MessageLogged $event): bool => $event->level === 'warning'
                && str_contains((string) $event->message, 'APP_DEBUG')
        );
    }

    public function test_manuscript_and_revision_uploads_reject_disguised_executables(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author);
        $payload = [
            'title' => 'A complete regional research manuscript',
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'history, education, society, culture',
            'manuscript' => UploadedFile::fake()->create('manuscript.pdf', 40, 'application/x-msdownload'),
        ];

        $this->actingAs($author)
            ->from(route('submissions.create'))
            ->post(route('submissions.store'), $payload)
            ->assertRedirect(route('submissions.create'))
            ->assertSessionHasErrors('manuscript');

        $this->assertDatabaseCount('submissions', 0);

        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'status' => SubmissionStatus::RevisionRequested,
        ]);

        $this->actingAs($author)
            ->post(route('submissions.revisions.store', $submission), [
                'manuscript' => UploadedFile::fake()->create('revised.pdf', 40, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('manuscript');
    }
}
