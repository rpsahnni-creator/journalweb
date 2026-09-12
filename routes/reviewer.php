<?php

use App\Http\Controllers\Reviewer\AssignmentController;
use App\Http\Controllers\Reviewer\AssignmentFileController;
use App\Http\Controllers\Reviewer\DashboardController as ReviewerDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:reviewer'])
    ->prefix('reviewer')
    ->name('reviewer.')
    ->group(function (): void {
        Route::get('/', ReviewerDashboardController::class)->name('dashboard');
        Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
        Route::post('assignments/{assignment}/accept', [AssignmentController::class, 'accept'])->name('assignments.accept');
        Route::post('assignments/{assignment}/decline', [AssignmentController::class, 'decline'])->name('assignments.decline');
        Route::post('assignments/{assignment}/review', [AssignmentController::class, 'storeReview'])->name('assignments.review.store');
        Route::get('assignments/{assignment}/files/{file}', [AssignmentFileController::class, 'download'])->name('assignments.files.download');
    });
