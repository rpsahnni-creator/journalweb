# SRT Journal of Multidisciplinary Research — Project Status & Audit Report

**Date of Audit:** September 12, 2026  
**Target Production Domain:** `journal.srtc.ac.in`  
**Application Framework:** Laravel 12 (PHP 8.2+)  
**Repository Context:** Academic Journal Management System for S.R.T. College, Dhamni (a constituent unit of Sido Kanhu Murmu University, Dumka).  
**Audit Scope:** Full codebase inspection (routes, controllers, models, migrations, Blade views, seeders, security policies, and test suite verification).

---

## Executive Summary

| Category | Status | Summary |
| :--- | :--- | :--- |
| **Test Suite** | ✅ Passed | 177 tests passed (1,555 assertions, 0 failures). |
| **Core Workflow (Portal)** | ✅ Done & Tested | Double-blind submission, review assignment, revision rounds, decision making, and publishing pipeline are complete and verified. |
| **Public Information Pages** | ⚠️ Partial | Core pages exist, but Editorial Board has 4 of 5 placeholder members, publication frequency is undeclared, and APC policy is absent. |
| **Editorial Board Quality** | 🔴 Blocking ISSN | 1 real member (Editor-in-Chief); 4 members are placeholders (`[Editorial Board Member — Name Pending]`). |
| **Issue / Inaugural State** | ⚠️ Partial | Current issue contains 0 non-demo articles; code logic checks `= 0` articles instead of `< 5` articles for the "Call for Papers" fallback. |
| **Production Readiness** | ⚠️ Action Required | Default `admin@example.com` credential with password `password` remains in DB; `MAIL_MAILER=log`; missing `deploy.sh`, Apache vhost, and `.env.production.example`. |

---

## Test Suite Results

Full run of `php artisan test`:

```text
   PASS  Tests\Unit\Enums\ArticleStatusTest
  ✓ it defines the complete workflow status list                                    0.01s
  ✓ only published articles are publicly visible
  ✓ it allows only valid workflow transitions

   PASS  Tests\Unit\Models\IssueCitationDateTest
  ✓ citation publication date formats month year as yyyy mm                         0.76s
  ✓ citation publication date falls back to published at                            0.03s

   PASS  Tests\Unit\Support\SafeRedirectTest
  ✓ it rejects external and protocol relative urls                                  0.04s

   PASS  Tests\Feature\Admin\AdminAccessTest (6 tests)                             1.00s
   PASS  Tests\Feature\Admin\AuditLogAndPlaceholdersTest (2 tests)                 0.34s
   PASS  Tests\Feature\Admin\EditorialBoardTest (2 tests)                          0.32s
   PASS  Tests\Feature\Admin\IssuePublicationTest (4 tests)                        0.62s
   PASS  Tests\Feature\Admin\JournalSettingsTest (4 tests)                         0.50s
   PASS  Tests\Feature\Admin\PolicyManagementTest (3 tests)                        0.54s
   PASS  Tests\Feature\Admin\RoleManagementTest (3 tests)                          0.38s
   PASS  Tests\Feature\Admin\UserManagementTest (8 tests)                          1.06s
   PASS  Tests\Feature\Auth\AuthenticationTest (9 tests)                           1.94s
   PASS  Tests\Feature\Auth\AuthorizationTest (7 tests)                            1.07s
   PASS  Tests\Feature\Auth\EmailVerificationTest (2 tests)                         0.13s
   PASS  Tests\Feature\Auth\PasswordResetTest (2 tests)                             0.54s
   PASS  Tests\Feature\Auth\ProfileTest (5 tests)                                   0.32s
   PASS  Tests\Feature\Author\ManuscriptSubmissionTest (11 tests)                  1.75s
   PASS  Tests\Feature\CompleteApplicationTest (6 tests)                            2.41s
   PASS  Tests\Feature\DiscoverabilityTest (5 tests)                                0.47s
   PASS  Tests\Feature\Editorial\PublicationWorkflowTest (5 tests)                  1.11s
   PASS  Tests\Feature\Editorial\RevisionDecisionWorkflowTest (5 tests)             1.83s
   PASS  Tests\Feature\HealthCheckTest (1 test)                                     0.06s
   PASS  Tests\Feature\HomePageTest (1 test)                                        0.08s
   PASS  Tests\Feature\Models\JournalDomainModelTest (9 tests)                      0.58s
   PASS  Tests\Feature\NotificationStructureTest (7 tests)                          1.14s
   PASS  Tests\Feature\PeerReview\PeerReviewWorkflowTest (7 tests)                  2.93s
   PASS  Tests\Feature\Policies\ArticlePolicyTest (3 tests)                         0.41s
   PASS  Tests\Feature\PublicWebsiteTest (17 tests)                                 5.85s
   PASS  Tests\Feature\Security\ProductionHardeningTest (4 tests)                   0.69s
   PASS  Tests\Feature\Security\SecurityAuditTest (14 tests)                        2.24s
   PASS  Tests\Feature\Seeders\RolePermissionSeederTest (1 test)                    0.23s
   PASS  Tests\Feature\SrtJournalIdentityTest (2 tests)                             0.59s
   PASS  Tests\Feature\Submissions\ManuscriptPortalTest (6 tests)                   0.93s
   PASS  Tests\Feature\Submissions\RevisionAndNotificationTest (3 tests)            0.68s
   PASS  Tests\Feature\Submissions\SubmissionReviewWorkflowTest (7 tests)           0.97s

  Tests:    177 passed (1555 assertions)
  Duration: 34.74s
```

---

## Detailed Audit Checklist (Groups A – I)

### A. Public Site — About Journal

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **About the Journal** | ✅ Done & tested | Route `/about` (`about`), `PolicyPageController@about`, view `pages.about`. Dynamic from database `journal_policies` (`type='about'`) or fallback to `JournalCopy::ABOUT_TEXT`. Tested in `PublicWebsiteTest`. |
| **Aims and Scope** | ✅ Done & tested | Route `/aims-and-scope` (`aims-and-scope`), `PolicyPageController@aimsAndScope`, view `pages.policy`. Dynamic from `journal_policies` (`type='aims_and_scope'`) with subject coverage of Santhal Pargana region and multidisciplinary fields. |
| **Editorial Board** | ⚠️ Partially built | Route `/editorial-board` (`editorial-board`), `EditorialBoardController`, view `pages.editorial-board`. **Board Status:** Out of 5 seeded records, only **1 is real** (`Dr. Shambhu Kumar Singh`, Editor-in-Chief); **4 are placeholders** (`[Editorial Board Member — Name Pending]`) with `is_public=false`. The public page only displays the 1 real member. The admin panel warns: *"4 of 5 editorial board members still need real names before ISSN submission."* |
| **Publication Information** | ⚠️ Partially built | Copyright & licensing is built at `/copyright-and-license` (`PolicyPageController@copyright`). However, **publication frequency** (e.g. Biannual) is undeclared anywhere in the codebase or database, and **indexing status** has no dedicated page (only a one-line disclaimer in the footer). |
| **Publisher Information** | ⚠️ Partially built | **Footer-only.** Displayed in `components/site-footer.blade.php` using `journal->setting('publisher_name')`, `publisher_address`, and `publisher_email` (Sri Raghunandan Tiwari College, Dhamni, Godda). No dedicated `/publisher` page exists. |

---

### B. For Authors

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Author Guidelines** | ✅ Done & tested | Route `/author-guidelines` (`author-guidelines`), `PolicyPageController@authorGuidelines`, view `pages.author-guidelines`. Outlines word count (3,000–6,000 words), similarity limit (15%), APA 7th edition, double-blind review, and registration link. |
| **Article Processing Charges (APC)** | ❌ Not built | No route, controller action, policy type, or view exists. Open access journals must explicitly state whether they charge APCs or are free (diamond open access) for ISSN and indexing eligibility. |
| **Submission Checklist** | ❌ Not built | No standalone submission checklist page or interactive pre-submission checklist component exists. Only a 7-item ordered list inside `/author-guidelines`. |
| **Manuscript Preparation** | ❌ Not built | No dedicated manuscript preparation page exists separate from the brief summary on the Author Guidelines page. |
| **Submit an Article** | ✅ Done & tested | Route `/submissions/create` (`submissions.create`), `POST /submissions` (`submissions.store`), `SubmissionController`. Form includes title, live-counted abstract (150–250 words), keywords (4–6), co-authors, and private PDF/DOCX file upload to `storage/app/private/manuscripts`. Triggers notifications and records version 1. |
| **"My Submissions" Author Status Page** | ✅ Done & tested | Routes `/submissions` (`submissions.index`) and `/submissions/{submission}` (`submissions.show`), `SubmissionController`. Authors track submission status, download uploaded files, view anonymized reviewer comments upon revision request, and upload revised manuscripts. |

---

### C. Peer Review

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Peer Review Policy** | ✅ Done & tested | Route `/peer-review-policy` (`peer-review-policy`), `PolicyPageController@peerReview`, view `pages.policy`. Outlines double-blind process, confidentiality of manuscripts, and editorial independence. |
| **Reviewer Guidelines** | ❌ Not built | No dedicated reviewer guidelines page, route, or policy type exists. |
| **Review Process Page** | ❌ Not built | No public standalone visual or detailed explanatory workflow page exists beyond the brief Peer Review Policy. |
| **Backend: Reviewer Assignment, Blinded View, Recommendations, Review Rounds** | ✅ Done & tested | Routes in `admin.php` and `web.php` (`/reviews`, `/admin/submissions/{submission}/reviews`). Reviewer assignment verifies reviewer eligibility and prevents assigning the author (`AssignSubmissionReviewersRequest`). Reviewers receive blinded metadata (author details withheld) and files are served as `manuscript.pdf`/`manuscript.docx`. Recommendations (Accept, Revisions, Reject), private editor comments, and author-facing comments are collected. Revision uploads increment `review_round`. |

---

### D. Publication Ethics

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Publication Ethics Statement** | ✅ Done & tested | Route `/publication-ethics` (`publication-ethics`), `PolicyPageController@publicationEthics`, view `pages.policy`. Sets standards for authors, reviewers, and editors. |
| **Plagiarism Policy** | ✅ Done & tested | Route `/plagiarism-policy` (`plagiarism-policy`), `PolicyPageController@plagiarism`, view `pages.plagiarism-policy`. Declares zero-tolerance policy, 15% similarity threshold, and institutional retraction terms. |
| **Conflict of Interest Policy** | ⚠️ Partially built | Conflict of interest is declared on manuscripts (`conflict_of_interest_declared` and statement field), and mentioned in one sentence in Publication Ethics, but **no standalone public Conflict of Interest policy page** exists. |
| **Corrections and Retractions Policy** | ⚠️ Partially built | Retractions are mentioned in a single sentence in the Plagiarism Policy. No dedicated Corrections and Retractions policy page exists. |
| **Complaints and Appeals Policy** | ❌ Not built | Zero code or policy text exists regarding formal editorial complaints, appeals, or dispute escalation. |

---

### E. Issues

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Current Issue** | ⚠️ Partially built / Logic discrepancy | Route `/issues/current` (`issues.current`), `IssueController@current`, view `issues.show`. **Logic discrepancy:** The requirement specifies falling back to the "Call for Papers" empty state when `< 5` articles. The current implementation in `IssueController` (line 19) and `HomeController` (line 24) checks `=== 0` / `isEmpty()`. If 1 to 4 articles are published, it currently attempts to display the issue rather than falling back to the Call for Papers state. |
| **Previous Issues Archive** | ✅ Done & tested | Route `/issues` (`issues.index`, alias `/archive`), `IssueController@index`, view `issues.index`. Paginates published issues, displays volume/issue badges and article counts, and includes keyword search across published articles. |
| **Special Issues** | ❌ Not built | The `issues` table lacks a `type`, `is_special_issue`, or categorization column. No filter, badge, or archive section exists for special issues. |

---

### F. Resources

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Article Search** | ✅ Done & tested | Route `/articles` (`articles.index`), `ArticleController@index`, view `articles.index`. Full-text search by title, abstract, keyword, or author name over published non-demo articles. Also integrated into `/issues`. |
| **Download Templates** | ❌ Not built | No Word (.docx) manuscript template files or LaTeX templates exist in storage/public, and no download route is provided. |
| **Publication Policies** | ⚠️ Partially built (Overlap with D) | Individual policies exist as standalone pages (`/peer-review-policy`, `/publication-ethics`, `/plagiarism-policy`, `/copyright-and-license`) and as card links on `/about`. There is no dedicated single aggregate "Policies" index page. |
| **FAQs** | ❌ Not built | No FAQ page, route, database model, or view exists. |

---

### G. Core Workflow (End-to-End Verification)

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Submission → Private Storage → Status** | ✅ Done & tested | File uploaded via `SubmissionController@store` → saved by `SubmissionFileStore` to `storage/app/private/manuscripts/{user_id}/{uuid}.{ext}` on private disk (`visibility='private'`, `serve=false`). Initial status: `submitted`. |
| **Reviewer Assignment (Double-Blind)** | ✅ Done & tested | `SubmissionReviewAssignmentController@store` checks `AssignSubmissionReviewersRequest` (enforces `id != author_id`, `is_reviewer=true`). Reviewers only receive blinded submission fields; manuscript download filename is forced to `manuscript.pdf`/`manuscript.docx`. |
| **Review Submission** | ✅ Done & tested | `SubmissionReviewController@update` validates `ReviewRecommendation` enum, `comments_to_editor`, and `comments_to_author`. Sets status to `submitted` and records timestamp. |
| **Revision Upload & Versioning** | ✅ Done & tested | `SubmissionController@storeRevision` calls `submission->recordVersion()`, increments `review_round`, uploads file to private storage, and resets status to `submitted`. |
| **Workflow Notifications** | ✅ Done & tested | Full notifications dispatched: `SubmissionReceived` (author & editors), `ReviewerAssigned` (reviewer), `ReviewSubmitted` (editors), `SubmissionDecisionMade` (author with compiled comments), `RevisionResubmitted` (editors). Tested in `NotificationStructureTest`. |
| **Publish to Issue** | ✅ Done & tested | `Admin\SubmissionController@publish` wraps conversion in a DB transaction: creates/updates `Article`, assigns `issue_id`, auto-generates slug via `Article::booted()`, sets `published_at=now()`, `is_demo=false`, and updates submission status to `published`. |
| **Citation Box & Meta Tags** | ✅ Done & tested | `Article::scholarMetaTags()` generates Highwire (`citation_title`, `citation_author`, `citation_journal_title`, `citation_publication_date`, `citation_issn`, `citation_volume`, `citation_issue`, `citation_pdf_url`) and Dublin Core tags in `<head>`. View `articles/show.blade.php` includes interactive "How to cite this article" citation box with clipboard copy. |
| **Dynamic Sitemap (`/sitemap.xml`)** | ✅ Done & tested | `SitemapController` dynamically generates XML comprising static pages, published issues, and non-demo published articles (`where('is_demo', false)`). |
| **Robots Directive (`/robots.txt`)** | ✅ Done & tested | `RobotsController` outputs `Disallow: /admin`, `Disallow: /submissions`, `Disallow: /reviews`, and links `Sitemap: .../sitemap.xml`. |
| **Demo Article Exclusion** | ✅ Done & tested | `Article::scopeVisibleInEnvironment()` filters out `is_demo=true` records in production environment across home, issues, sitemap, and search. Tested in `PublicWebsiteTest`. |

---

### H. Security & Production Readiness

| Item | Status | Evidence & Verification |
| :--- | :---: | :--- |
| **Default Admin Credential Warning** | ✅ Done & tested | `ProductionSafety::warnIfDefaultAdminExists()` checks if `admin@example.com` has password `password` and logs a warning in production. Verified in `ProductionHardeningTest`. **Database check:** Account currently exists with the default password. |
| **File Upload MIME Sniffing** | ✅ Done & tested | `ManuscriptUpload` validates file headers using `mimetypes` and `File::types(['pdf', 'docx'])` (real file inspection, not client extension). Rejects disguised executables. Tested in `ProductionHardeningTest`. |
| **Route Throttling** | ✅ Done & tested | `throttle:10,1` on `register` and `submissions.create`/`store`/`revisions`; `throttle:5,1` on `login` POST; `throttle:6,1` on contact, password reset, and verification. |
| **`.env.production.example`** | ❌ Not built | File does not exist at project root (only `.env` and `.env.example` are present). |
| **Deployment Script (`deploy.sh`)** | ❌ Not built | No automated or idempotent `deploy.sh` script exists in the repository. |
| **Apache VirtualHost Sample Config** | ❌ Not built | No Apache virtual host configuration file (`.conf`) pointing to `/public` exists in the repository. |
| **Health Check Route (`/up`)** | ✅ Done & tested | Registered in `bootstrap/app.php` (`health: '/up'`). Tested and returning HTTP 200. Also `/health` returns JSON `{"status":"ok","database":"ok"}`. |

---

### I. Data Quality Flags & Explicit Inquiries

| Inquiry | Finding | Audit Evidence |
| :--- | :---: | :--- |
| **Editorial Board: Real vs. Placeholder** | **1 Real / 4 Placeholders** | Database query on `editorial_board_members`: 1 member is real (`Dr. Shambhu Kumar Singh`, Editor-in-Chief, `is_public=true`); 4 members are `[Editorial Board Member — Name Pending]` (`is_public=false`). |
| **Current Issue & Article Count** | **Issue #2 is current / 0 non-demo articles** | Issue #2 ("Latest sample issue", Vol. 2, No. 1) has `is_current=true`. It contains 2 published articles, but both are seeded demo articles (`is_demo=true`). Non-demo article count is **0**. |
| **`MAIL_MAILER` Setting** | **`log`** | Confirmed in `.env`: `MAIL_MAILER=log`, `MAIL_FROM_ADDRESS="hello@example.com"`. Real outbound email delivery is disabled. |
| **Default Admin in Database** | **Exists with default password** | Confirmed in `users` table: User `admin@example.com` ("Development Admin") exists, and `Hash::check('password', $admin->password)` evaluates to `true`. |

---

## Placeholders & TODOs Found

Below is the complete inventory of TODO comments and placeholder strings across project source files (excluding `vendor`, `node_modules`, and compiled `public/build` assets):

### 1. Source Code TODO Comments (10 matches)

| File Path | Line | Code Snippet |
| :--- | :---: | :--- |
| `app/Support/JournalCopy.php` | 19 | `// TODO: confirm the manuscript word limit with the editorial office before publishing live.` |
| `app/Support/JournalCopy.php` | 22 | `// TODO: confirm the similarity-index threshold with the editorial office before publishing live.` |
| `app/Support/JournalCopy.php` | 25 | `// TODO: confirm the submissions email with the editorial office before publishing live.` |
| `database/seeders/JournalContentSeeder.php` | 167 | `// TODO: confirm exact designation/department with the user before going live` |
| `database/seeders/JournalContentSeeder.php` | 173 | `// TODO: confirm real email` |
| `database/seeders/JournalIdentitySeeder.php` | 75 | `// TODO: confirm exact designation/department with the user before going live` |
| `database/seeders/JournalIdentitySeeder.php` | 81 | `// TODO: confirm real email` |
| `resources/views/pages/author-guidelines.blade.php` | 8 | `{{-- TODO: confirm the manuscript word limit (3,000–6,000 words) before publishing live. --}}` |
| `resources/views/pages/author-guidelines.blade.php` | 11 | `{{-- TODO: confirm the similarity-index threshold (15%) before publishing live. --}}` |
| `resources/views/pages/author-guidelines.blade.php` | 15 | `{{-- TODO: confirm the submissions email (journal@srtc.ac.in) before publishing live. --}}` |

### 2. Editorial Board Placeholders (17 matches)

| File Path | Line | Code Snippet |
| :--- | :---: | :--- |
| `app/Support/JournalCopy.php` | 28 | `public const PLACEHOLDER_BOARD_NAME = '[Editorial Board Member — Name Pending]';` |
| `app/Support/JournalCopy.php` | 30 | `public const PLACEHOLDER_BOARD_DESIGNATION = '[Designation Pending]';` |
| `app/Support/JournalCopy.php` | 32 | `public const PLACEHOLDER_BOARD_INSTITUTION = '[Institution Pending]';` |
| `app/Support/JournalCopy.php` | 34 | `public const PLACEHOLDER_BOARD_EMAIL = '[pending@srtc.ac.in]';` |
| `database/seeders/JournalContentSeeder.php` | 186 | `'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,` |
| `database/seeders/JournalContentSeeder.php` | 189 | `'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,` |
| `database/seeders/JournalContentSeeder.php` | 190 | `'role_title' => JournalCopy::PLACEHOLDER_BOARD_DESIGNATION,` |
| `database/seeders/JournalContentSeeder.php` | 192 | `'affiliation' => JournalCopy::PLACEHOLDER_BOARD_INSTITUTION,` |
| `database/seeders/JournalContentSeeder.php` | 194 | `'email' => JournalCopy::PLACEHOLDER_BOARD_EMAIL,` |
| `database/seeders/JournalIdentitySeeder.php` | 94 | `'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,` |
| `database/seeders/JournalIdentitySeeder.php` | 97 | `'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,` |
| `database/seeders/JournalIdentitySeeder.php` | 98 | `'role_title' => JournalCopy::PLACEHOLDER_BOARD_DESIGNATION,` |
| `database/seeders/JournalIdentitySeeder.php` | 100 | `'affiliation' => JournalCopy::PLACEHOLDER_BOARD_INSTITUTION,` |
| `database/seeders/JournalIdentitySeeder.php` | 102 | `'email' => JournalCopy::PLACEHOLDER_BOARD_EMAIL,` |
| `tests/Feature/SrtJournalIdentityTest.php` | 49 | `->assertDontSee(JournalCopy::PLACEHOLDER_BOARD_NAME, false);` |
| `tests/Feature/SrtJournalIdentityTest.php` | 61 | `->assertSee(JournalCopy::PLACEHOLDER_BOARD_NAME, false)` |
| `resources/views/admin/editorial-board/index.blade.php` | 13 | `{{ $pendingNameCount }} of {{ $boardMemberCount }} editorial board members still need real names before ISSN submission.` |

### 3. Application "Pending" Strings (2 UI occurrences)

| File Path | Line | Context |
| :--- | :---: | :--- |
| `resources/views/editorial/dashboard.blade.php` | 58 | Metric title: `Pending invitations` |
| `resources/views/reviewer/dashboard.blade.php` | 40 | Empty state: `No pending invitations.` |

---

## Broken Navigation & Unlinked Routes

### 1. Broken Nav Links (404s)
- **Zero broken links found.** All `route('...')` calls across 108 Blade templates resolve to active named routes. There are no hardcoded relative paths that 404.

### 2. Routes That Exist But Are Not Linked in Any Views/Controllers (6 routes)

| Route Name | URI | Reason / Explanation |
| :--- | :--- | :--- |
| `submissions.versions.download` | `/submissions/{submission}/versions/{version}/download` | The controller method (`SubmissionController@downloadVersion`) and route exist, but `submissions/show.blade.php` only links the current manuscript download (`submissions.download`), leaving author past-version downloads unlinked. |
| `archive` | `/archive` | Alias route for `/issues` (`issues.index`). All templates use `route('issues.index')`, leaving `/archive` unreferenced in the UI. |
| `health` | `/health` | JSON machine health check endpoint; intentionally not linked in UI. |
| `robots` | `/robots.txt` | Crawler directive endpoint; intentionally not linked in UI. |
| `password.reset` | `/reset-password/{token}` | Reached only via password reset notification emails; not linked in Blade views. |
| `verification.verify` | `/verify-email/{id}/{hash}` | Signed URL delivered via email verification notifications; not linked in Blade views. |

### 3. Dual-Workflow Architectural Disconnect (Important Observation)
The codebase contains **two parallel workflow implementations**:
1. **The Portal Workflow (Active in Top Nav):** Routes `/submissions/*`, `/admin/submissions/*`, and `/reviews/*` backed by `Submission`, `SubmissionReview`, and `SubmissionVersion` models.
2. **The Multi-Role Workspace Workflow (Secondary):** Routes `/author/*`, `/editorial/*`, and `/reviewer/*` backed by `Article`, `ReviewerAssignment`, and `ArticleFile` models.

These secondary workspace routes (`author.dashboard`, `editorial.dashboard`, `reviewer.dashboard`) are **only linked as cards on the `/dashboard` hub page** and are completely absent from the primary public navigation header (`components/navigation.blade.php`).

---

## Recommended Next Steps

Ordered by what blocks **ISSN application readiness** first:

### Phase 1: ISSN Application Blockers (Highest Priority)

1. **Populate Real Editorial Board Members (Mandatory for ISSN):**
   - Replace the 4 `[Editorial Board Member — Name Pending]` entries in the database with verified academic board members (names, institutional affiliations, departments, official email addresses).
   - Ensure a minimum of 5–7 qualified members across Humanities, Social Sciences, and Natural Sciences are marked `is_public=true` to render on `/editorial-board`.
2. **Publish Article Processing Charges (APC) Policy (Mandatory for Open Access / ISSN):**
   - Add a dedicated public page (or policy section) explicitly stating the fee policy (e.g. *"SRT Journal of Multidisciplinary Research does not charge any article submission or processing fees (Diamond Open Access)"*).
3. **Declare Publication Frequency (Mandatory for ISSN):**
   - Add the official journal periodicity (e.g. *Biannual: June and December*) to the public About page, publication policy, and footer metadata.
4. **Clarify Publisher Information:**
   - Create an explicit "Publisher Information" section or dedicated page on the public site detailing Sri Raghunandan Tiwari College (S.R.T. College), constituent unit of Sido Kanhu Murmu University, Dumka, with physical postal address and institutional contact.
5. **Publish Inaugural Real Articles (< 5 Fallback Correction):**
   - Correct the logic in `HomeController` (line 24) and `IssueController` (line 19) to verify whether the published non-demo article count is `< 5` rather than `=== 0`, matching academic journal conventions.
   - Ingest and publish at least 5 real research articles into Volume 1, Issue 1 before submitting the ISSN application.

### Phase 2: Editorial & Publication Policies (Compliance & Transparency)

6. **Add Missing Ethics Policies:**
   - Create dedicated public policy pages for:
     - **Corrections and Retractions Policy** (procedure for publishing errata, corrigenda, and formal retractions).
     - **Complaints and Appeals Policy** (mechanism for authors to appeal editorial decisions or report ethical violations).
     - **Conflict of Interest Policy** (formal public text for author, reviewer, and editor financial/non-financial disclosures).
     - **Reviewer Guidelines** (expectations, review rubrics, turnaround timelines).
7. **Provide Downloadable Manuscript Templates:**
   - Add a downloadable `.docx` manuscript template conforming to APA 7th edition formatting and link it from `/author-guidelines`.

### Phase 3: Infrastructure & Production Hardening

8. **Secure Default Admin Credentials:**
   - Immediately change the password of `admin@example.com` in the production environment to silence the `ProductionSafety` warning.
9. **Configure Production Mail Driver:**
   - Update `MAIL_MAILER` from `log` to `smtp` with authenticated institutional SMTP credentials (`journal@srtc.ac.in`) and configure `MAIL_FROM_ADDRESS`.
10. **Create Deployment & Server Artifacts:**
    - Create `.env.production.example` reflecting production keys, domain `journal.srtc.ac.in`, and disabled debug mode (`APP_DEBUG=false`).
    - Create an idempotent `deploy.sh` script (handling `composer install --no-dev`, `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, asset compilation).
    - Provide an Apache VirtualHost configuration sample pointing to `DocumentRoot /path/to/journal-system/public` with `AllowOverride All` for `.htaccess`.
11. **UI Cleanup:**
    - Link `submissions.versions.download` on the author submission detail page so authors can retrieve past revision files.
    - Reconcile the two workflow architectures (consolidate the `/submissions` portal and the `/author/*` / `/editorial/*` multi-role workspace to avoid operational confusion).
