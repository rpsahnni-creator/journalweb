<?php

use App\Http\Middleware\EnsureAdminOrEditor;
use App\Http\Middleware\EnsureEditor;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureReviewer;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
            'active' => EnsureUserIsActive::class,
            'editor' => EnsureEditor::class,
            'admin_or_editor' => EnsureAdminOrEditor::class,
            'reviewer' => EnsureReviewer::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->validateCsrfTokens(except: ['oai']);
        $middleware->trustHosts();

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
