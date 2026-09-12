<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\EmailSubscription;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageWidthConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private const HOME_WIDTH_CLASSES = 'mx-auto max-w-6xl px-4 sm:px-6 lg:px-8';

    public function test_public_pages_use_the_home_page_content_width(): void
    {
        $this->seed(DatabaseSeeder::class);

        $author = $this->createUserWithRole(RoleSlug::Author);
        $submission = Submission::factory()->create(['user_id' => $author->id]);
        $article = Article::query()
            ->where('slug', 'sample-article-keeping-unpublished-manuscripts-private')
            ->firstOrFail();
        $resetUser = User::factory()->create();
        $resetToken = Password::broker()->createToken($resetUser);
        $subscription = EmailSubscription::start('alerts@example.com');
        $unverified = User::factory()->unverified()->create();
        $reviewer = User::factory()->reviewer()->create();
        $review = SubmissionReview::factory()->create(['reviewer_id' => $reviewer->id]);

        $guestUrls = [
            'home' => route('home'),
            'about' => route('about'),
            'aims-and-scope' => route('aims-and-scope'),
            'editorial-board' => route('editorial-board'),
            'publisher' => route('publisher'),
            'author-guidelines' => route('author-guidelines'),
            'article-processing-charges' => route('article-processing-charges'),
            'submission-checklist' => route('submission-checklist'),
            'manuscript-preparation' => route('manuscript-preparation'),
            'peer-review-policy' => route('peer-review-policy'),
            'reviewer-guidelines' => route('reviewer-guidelines'),
            'review-process' => route('review-process'),
            'publication-ethics' => route('publication-ethics'),
            'plagiarism-policy' => route('plagiarism-policy'),
            'conflict-of-interest' => route('conflict-of-interest'),
            'corrections-and-retractions' => route('corrections-and-retractions'),
            'complaints-and-appeals' => route('complaints-and-appeals'),
            'copyright-and-license' => route('copyright-and-license'),
            'contact' => route('contact'),
            'issues.index' => route('issues.index'),
            'archive' => route('archive'),
            'issues.current' => route('issues.current'),
            'issues.show' => route('issues.show', ['volume' => 2, 'issue' => 1]),
            'articles.index' => route('articles.index'),
            'articles.show' => route('articles.show', $article),
            'register' => route('register'),
            'login' => route('login'),
            'password.request' => route('password.request'),
            'password.reset' => route('password.reset', ['token' => $resetToken, 'email' => $resetUser->email]),
            'subscribe.confirm' => route('subscribe.confirm', $subscription->confirm_token),
            'unsubscribe' => route('unsubscribe', $subscription->confirm_token),
        ];

        foreach ($guestUrls as $name => $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(self::HOME_WIDTH_CLASSES, false);
        }

        $this->actingAs($author);
        foreach ([
            'dashboard' => route('dashboard'),
            'profile.edit' => route('profile.edit'),
            'submissions.index' => route('submissions.index'),
            'submissions.create' => route('submissions.create'),
            'submissions.show' => route('submissions.show', $submission),
            'notifications.index' => route('notifications.index'),
        ] as $name => $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(self::HOME_WIDTH_CLASSES, false);
        }

        $this->actingAs($reviewer);
        foreach ([
            'reviews.index' => route('reviews.index'),
            'reviews.show' => route('reviews.show', $review),
        ] as $name => $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee(self::HOME_WIDTH_CLASSES, false);
        }

        $this->actingAs($unverified)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee(self::HOME_WIDTH_CLASSES, false);

        $this->assertEqualsCanonicalizing(
            $this->expectedVisualPublicRouteNames(),
            $this->discoveredVisualPublicRouteNames(),
        );
    }

    /**
     * @return list<string>
     */
    private function expectedVisualPublicRouteNames(): array
    {
        return [
            'home',
            'about',
            'aims-and-scope',
            'editorial-board',
            'publisher',
            'author-guidelines',
            'article-processing-charges',
            'submission-checklist',
            'manuscript-preparation',
            'peer-review-policy',
            'reviewer-guidelines',
            'review-process',
            'publication-ethics',
            'plagiarism-policy',
            'conflict-of-interest',
            'corrections-and-retractions',
            'complaints-and-appeals',
            'copyright-and-license',
            'contact',
            'issues.index',
            'archive',
            'issues.current',
            'issues.show',
            'articles.index',
            'articles.show',
            'register',
            'login',
            'password.request',
            'password.reset',
            'subscribe.confirm',
            'unsubscribe',
            'dashboard',
            'profile.edit',
            'submissions.index',
            'submissions.create',
            'submissions.show',
            'notifications.index',
            'reviews.index',
            'reviews.show',
            'verification.notice',
        ];
    }

    /**
     * @return list<string>
     */
    private function discoveredVisualPublicRouteNames(): array
    {
        $skipNames = [
            'health',
            'sitemap',
            'robots',
            'oai',
            'feed.articles',
            'feed.issues',
            'feed.issue',
            'articles.pdf',
            'articles.export.bibtex',
            'articles.export.ris',
            'articles.export.apa',
            'articles.export.mla',
            'articles.export.chicago',
            'downloads.manuscript-template',
            'submissions.download',
            'submissions.versions.download',
            'reviews.manuscript',
            'notifications.show',
            'verification.verify',
            'locale',
            'pwa.manifest',
            'pwa.service-worker',
        ];

        return collect(Route::getRoutes())
            ->filter(fn ($route): bool => in_array('GET', $route->methods(), true))
            ->filter(fn ($route): bool => is_string($route->getName()) && $route->getName() !== '')
            ->reject(function ($route) use ($skipNames): bool {
                $uri = $route->uri();
                foreach (['admin', 'editorial', 'reviewer', 'author'] as $prefix) {
                    if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                        return true;
                    }
                }

                return in_array($route->getName(), $skipNames, true);
            })
            ->map(fn ($route): string => $route->getName())
            ->values()
            ->all();
    }
}
