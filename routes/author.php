<?php

use App\Http\Controllers\Author\DashboardController as AuthorDashboardController;
use App\Http\Controllers\Author\ManuscriptController;
use App\Http\Controllers\Author\ManuscriptFileController;
use App\Http\Controllers\Author\ProfileController as AuthorProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:author'])->prefix('author')->name('author.')->group(function (): void {
    Route::get('/', AuthorDashboardController::class)->name('dashboard');

    Route::get('profile', [AuthorProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [AuthorProfileController::class, 'update'])->name('profile.update');

    Route::get('manuscripts/create', [ManuscriptController::class, 'create'])->name('manuscripts.create');
    Route::post('manuscripts', [ManuscriptController::class, 'store'])->name('manuscripts.store');
    Route::get('manuscripts/{manuscript:id}', [ManuscriptController::class, 'show'])->name('manuscripts.show');
    Route::get('manuscripts/{manuscript:id}/edit', [ManuscriptController::class, 'edit'])->name('manuscripts.edit');
    Route::put('manuscripts/{manuscript:id}', [ManuscriptController::class, 'update'])->name('manuscripts.update');
    Route::post('manuscripts/{manuscript:id}/submit', [ManuscriptController::class, 'submit'])->name('manuscripts.submit');

    Route::post('manuscripts/{manuscript:id}/files', [ManuscriptFileController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('manuscripts.files.store');
    Route::get('manuscripts/{manuscript:id}/files/{file}', [ManuscriptFileController::class, 'download'])
        ->name('manuscripts.files.download');
    Route::delete('manuscripts/{manuscript:id}/files/{file}', [ManuscriptFileController::class, 'destroy'])
        ->name('manuscripts.files.destroy');
});
