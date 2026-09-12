<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EditorialBoardMemberController;
use App\Http\Controllers\Admin\IssueController as AdminIssueController;
use App\Http\Controllers\Admin\JournalSettingsController;
use App\Http\Controllers\Admin\PolicyController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\Admin\SubmissionReviewAssignmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VolumePlaceholderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);

    Route::get('journal', [JournalSettingsController::class, 'edit'])->name('journal.edit');
    Route::match(['put', 'post'], 'journal', [JournalSettingsController::class, 'update'])->name('journal.update');

    Route::resource('editorial-board', EditorialBoardMemberController::class)
        ->parameters(['editorial-board' => 'member'])
        ->except(['show']);

    Route::resource('policies', PolicyController::class)
        ->parameters(['policies' => 'policy'])
        ->except(['show']);

    Route::get('volumes', VolumePlaceholderController::class)->name('volumes.index');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware(['auth', 'verified', 'active', 'admin_or_editor'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('issues', [AdminIssueController::class, 'index'])->name('issues.index');
    Route::get('issues/create', [AdminIssueController::class, 'create'])->name('issues.create');
    Route::post('issues', [AdminIssueController::class, 'store'])->name('issues.store');
    Route::get('issues/{issue}/edit', [AdminIssueController::class, 'edit'])->name('issues.edit');
    Route::put('issues/{issue}', [AdminIssueController::class, 'update'])->name('issues.update');
    Route::post('issues/{issue}/current', [AdminIssueController::class, 'markCurrent'])->name('issues.current');
});

Route::middleware(['auth', 'verified', 'active', 'editor'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('submissions', [AdminSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [AdminSubmissionController::class, 'show'])->name('submissions.show');
    Route::put('submissions/{submission}', [AdminSubmissionController::class, 'update'])->name('submissions.update');
    Route::get('submissions/{submission}/download', [AdminSubmissionController::class, 'download'])->name('submissions.download');
    Route::get('submissions/{submission}/versions/{version}/download', [AdminSubmissionController::class, 'downloadVersion'])->name('submissions.versions.download');
    Route::post('submissions/{submission}/convert', [AdminSubmissionController::class, 'convert'])->name('submissions.convert');
    Route::post('submissions/{submission}/publish', [AdminSubmissionController::class, 'publish'])->name('submissions.publish');
    Route::get('articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::post('submissions/{submission}/reviews', [SubmissionReviewAssignmentController::class, 'store'])->name('submissions.reviews.store');
    Route::delete('submissions/{submission}/reviews/{submissionReview}', [SubmissionReviewAssignmentController::class, 'destroy'])->name('submissions.reviews.destroy');
});
