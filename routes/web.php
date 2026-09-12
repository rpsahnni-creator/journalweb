<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\CitationExportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorialBoardController;
use App\Http\Controllers\EmailSubscriptionController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\OaiPmhController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationInboxController;
use App\Http\Controllers\PolicyPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reviewer\SubmissionReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/locale/{locale}', LocaleController::class)->name('locale');
Route::get('/manifest.webmanifest', function () {
    return response((string) file_get_contents(public_path('manifest.webmanifest')), 200, [
        'Content-Type' => 'application/manifest+json; charset=UTF-8',
    ]);
})->name('pwa.manifest');
Route::get('/sw.js', function () {
    return response((string) file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
})->name('pwa.service-worker');
Route::get('/health', HealthController::class)->name('health');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::match(['GET', 'POST'], '/oai', OaiPmhController::class)->name('oai');

Route::get('/about', [PolicyPageController::class, 'about'])->name('about');
Route::get('/aims-and-scope', [PolicyPageController::class, 'aimsAndScope'])->name('aims-and-scope');
Route::get('/editorial-board', EditorialBoardController::class)->name('editorial-board');
Route::get('/author-guidelines', [PolicyPageController::class, 'authorGuidelines'])->name('author-guidelines');
Route::get('/peer-review-policy', [PolicyPageController::class, 'peerReview'])->name('peer-review-policy');
Route::get('/publication-ethics', [PolicyPageController::class, 'publicationEthics'])->name('publication-ethics');
Route::get('/plagiarism-policy', [PolicyPageController::class, 'plagiarism'])->name('plagiarism-policy');
Route::get('/copyright-and-license', [PolicyPageController::class, 'copyright'])->name('copyright-and-license');
Route::get('/article-processing-charges', [PolicyPageController::class, 'apc'])->name('article-processing-charges');
Route::get('/conflict-of-interest', [PolicyPageController::class, 'conflictOfInterest'])->name('conflict-of-interest');
Route::get('/corrections-and-retractions', [PolicyPageController::class, 'correctionsAndRetractions'])->name('corrections-and-retractions');
Route::get('/complaints-and-appeals', [PolicyPageController::class, 'complaintsAndAppeals'])->name('complaints-and-appeals');
Route::get('/reviewer-guidelines', [PolicyPageController::class, 'reviewerGuidelines'])->name('reviewer-guidelines');
Route::get('/review-process', [PolicyPageController::class, 'reviewProcess'])->name('review-process');
Route::get('/submission-checklist', [PolicyPageController::class, 'submissionChecklist'])->name('submission-checklist');
Route::get('/manuscript-preparation', [PolicyPageController::class, 'manuscriptPreparation'])->name('manuscript-preparation');
Route::get('/downloads/manuscript-template', [PolicyPageController::class, 'downloadManuscriptTemplate'])->name('downloads.manuscript-template');
Route::get('/publisher', [PolicyPageController::class, 'publisher'])->name('publisher');
Route::get('/issues/current', [IssueController::class, 'current'])->name('issues.current');
Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
Route::get('/archive', [IssueController::class, 'index'])->name('archive');
Route::get('/issues/{volume}/{issue}', [IssueController::class, 'show'])
    ->whereNumber('volume')
    ->whereNumber('issue')
    ->name('issues.show');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}/pdf', [ArticleController::class, 'pdf'])->name('articles.pdf');
Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');
Route::post('/chatbot', ChatbotController::class)
    ->middleware('throttle:20,1')
    ->name('chatbot');

// RSS / Atom feeds.
Route::get('/feed', [FeedController::class, 'articles'])->name('feed.articles');
Route::get('/feed/issues', [FeedController::class, 'issues'])->name('feed.issues');
Route::get('/feed/{volume}/{issue}', [FeedController::class, 'issue'])
    ->whereNumber('volume')
    ->whereNumber('issue')
    ->name('feed.issue');

Route::post('/subscribe', [EmailSubscriptionController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('subscribe');
Route::get('/subscribe/confirm/{token}', [EmailSubscriptionController::class, 'confirm'])->name('subscribe.confirm');
Route::get('/unsubscribe/{token}', [EmailSubscriptionController::class, 'unsubscribe'])->name('unsubscribe');

// Citation exports.
Route::get('/articles/{article}/export/bibtex', [CitationExportController::class, 'bibtex'])->name('articles.export.bibtex');
Route::get('/articles/{article}/export/ris', [CitationExportController::class, 'ris'])->name('articles.export.ris');
Route::get('/articles/{article}/export/apa', [CitationExportController::class, 'apa'])->name('articles.export.apa');
Route::get('/articles/{article}/export/mla', [CitationExportController::class, 'mla'])->name('articles.export.mla');
Route::get('/articles/{article}/export/chicago', [CitationExportController::class, 'chicago'])->name('articles.export.chicago');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/author.php';
require __DIR__.'/editorial.php';
require __DIR__.'/reviewer.php';

Route::middleware(['auth', 'verified', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [SubmissionController::class, 'create'])
        ->middleware('throttle:10,1')
        ->name('submissions.create');
    Route::post('/submissions', [SubmissionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('submissions.store');
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('/submissions/{submission}/revisions', [SubmissionController::class, 'storeRevision'])
        ->middleware('throttle:10,1')
        ->name('submissions.revisions.store');
    Route::get('/submissions/{submission}/download', [SubmissionController::class, 'download'])
        ->name('submissions.download');
    Route::get('/submissions/{submission}/versions/{version}/download', [SubmissionController::class, 'downloadVersion'])
        ->name('submissions.versions.download');

    Route::get('/notifications', [NotificationInboxController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationInboxController::class, 'show'])->name('notifications.show');
});

Route::middleware(['auth', 'verified', 'active', 'reviewer'])->group(function (): void {
    Route::get('/reviews', [SubmissionReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{submissionReview}', [SubmissionReviewController::class, 'show'])->name('reviews.show');
    Route::put('/reviews/{submissionReview}', [SubmissionReviewController::class, 'update'])->name('reviews.update');
    Route::get('/reviews/{submissionReview}/manuscript', [SubmissionReviewController::class, 'download'])->name('reviews.manuscript');
});
