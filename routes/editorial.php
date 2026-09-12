<?php

use App\Http\Controllers\Editorial\DashboardController as EditorialDashboardController;
use App\Http\Controllers\Editorial\IssueController as EditorialIssueController;
use App\Http\Controllers\Editorial\ManuscriptController as EditorialManuscriptController;
use App\Http\Controllers\Editorial\ManuscriptFileController as EditorialManuscriptFileController;
use App\Http\Controllers\Editorial\NotificationController;
use App\Http\Controllers\Editorial\VolumeController as EditorialVolumeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:journal_manager,editor_in_chief,section_editor,editor,copyeditor'])
    ->prefix('editorial')
    ->name('editorial.')
    ->group(function (): void {
        Route::get('/', EditorialDashboardController::class)->name('dashboard');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/test-mail', [NotificationController::class, 'sendTest'])
            ->middleware('throttle:3,1')
            ->name('notifications.test-mail');
        Route::get('manuscripts', [EditorialManuscriptController::class, 'index'])->name('manuscripts.index');
        Route::get('manuscripts/{manuscript:id}', [EditorialManuscriptController::class, 'show'])->name('manuscripts.show');
        Route::post('manuscripts/{manuscript:id}/screen', [EditorialManuscriptController::class, 'screen'])->name('manuscripts.screen');
        Route::post('manuscripts/{manuscript:id}/decision', [EditorialManuscriptController::class, 'decide'])->name('manuscripts.decision.store');
        Route::post('manuscripts/{manuscript:id}/reviewers', [EditorialManuscriptController::class, 'assign'])->name('manuscripts.reviewers.store');
        Route::get('manuscripts/{manuscript:id}/files/{file}', [EditorialManuscriptFileController::class, 'download'])->name('manuscripts.files.download');

        Route::resource('volumes', EditorialVolumeController::class)->except(['show']);
        Route::resource('issues', EditorialIssueController::class);
        Route::post('issues/{issue}/articles', [EditorialIssueController::class, 'assign'])->name('issues.articles.store');
        Route::put('issues/{issue}/articles/{placement}', [EditorialIssueController::class, 'updatePlacement'])->name('issues.articles.update');
        Route::delete('issues/{issue}/articles/{placement}', [EditorialIssueController::class, 'removePlacement'])->name('issues.articles.destroy');
        Route::put('issues/{issue}/order', [EditorialIssueController::class, 'reorder'])->name('issues.articles.reorder');
        Route::post('issues/{issue}/publish', [EditorialIssueController::class, 'publish'])->name('issues.publish');
        Route::post('issues/{issue}/unpublish', [EditorialIssueController::class, 'unpublish'])->name('issues.unpublish');
    });
