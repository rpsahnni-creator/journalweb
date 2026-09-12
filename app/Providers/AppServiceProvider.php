<?php

namespace App\Providers;

use App\Enums\RoleSlug;
use App\Models\IssueArticle;
use App\Models\JournalPolicy as ContentPolicy;
use App\Models\User;
use App\Policies\JournalContentPolicy;
use App\Support\ProductionSafety;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(8);

            if ($this->app->environment('production')) {
                $rule->mixedCase()->numbers()->uncompromised();
            }

            return $rule;
        });

        if ($this->app->environment('production')) {
            ProductionSafety::warnIfDebugEnabled();
            URL::forceScheme('https');
            config([
                'app.debug' => false,
                'session.secure' => true,
            ]);
            ProductionSafety::warnIfDefaultAdminExists();
        }

        Event::listen(Registered::class, SendEmailVerificationNotification::class);
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->save();
            }
        });

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isAdmin() ? true : null;
        });

        Gate::policy(ContentPolicy::class, JournalContentPolicy::class);

        Gate::define('access-admin', fn (User $user): bool => $user->hasRole(RoleSlug::Admin));
        Gate::define('access-editor', fn (User $user): bool => $user->isEditor());
        Gate::define('access-editorial', fn (User $user): bool => $user->isEditorial());
        Gate::define('access-reviewer', fn (User $user): bool => $user->isReviewer());
        Gate::define('access-author', fn (User $user): bool => $user->isAuthor());
        Gate::define('access-reader', fn (User $user): bool => $user->isReader() || $user->isAuthor() || $user->isReviewer() || $user->isEditorial());

        Route::bind('placement', fn (string $value) => IssueArticle::query()->findOrFail($value));
    }
}
