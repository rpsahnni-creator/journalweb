# Manuscript workflow comparison

**Date:** 12 September 2026  
**Scope:** Read-only analysis. No code was deleted, merged, or consolidated.  
**Database queried:** MySQL `journal_system` (app default connection), same day.

This document replaces the first (unresolved) 12 September 2026 flag. There was no existing `WORKFLOW_COMPARISON.md` in the project root.

Two complete manuscript products share one Laravel app and one `articles` table:

| System | Public URLs | Primary records | How users find it |
|--------|-------------|-----------------|-------------------|
| **Submissions portal** (keep) | `/submissions/*`, `/admin/submissions/*`, `/reviews/*` | `Submission`, `SubmissionReview`, `SubmissionVersion` | Site nav, author guidelines, admin sidebar |
| **Author / Editorial / Reviewer workspace** (remove manuscript half) | `/author/*`, `/editorial/*`, `/reviewer/*` | `Article` as manuscript + `Revision`, `ArticleFile`, `ReviewerAssignment`, `reviews`, `EditorialDecision` | `/dashboard` hub cards + post-login `User::dashboardRoute()` only |

A third Article writer was added after the first flag:

- **Add Article Directly** — `POST /admin/articles` — creates a published `Article` with no `Submission` and no workspace relations.

---

## 1. Route / controller / view inventory

Route files load from `routes/web.php` (`require` of `admin.php`, `author.php`, `editorial.php`, `reviewer.php`). There is no application `RouteServiceProvider`.

### 1.1 Submissions portal

#### Author (`routes/web.php`, middleware `auth`, `verified`, `active`)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/submissions` | `SubmissionController@index` | `submissions.index` |
| GET | `/submissions/create` | `SubmissionController@create` | `submissions.create` |
| POST | `/submissions` | `SubmissionController@store` | `submissions.store` |
| GET | `/submissions/{submission}` | `SubmissionController@show` | `submissions.show` |
| POST | `/submissions/{submission}/revisions` | `SubmissionController@storeRevision` | `submissions.revisions.store` |
| GET | `/submissions/{submission}/download` | `SubmissionController@download` | `submissions.download` |
| GET | `/submissions/{submission}/versions/{version}/download` | `SubmissionController@downloadVersion` | `submissions.versions.download` |

Related (shared inbox, not exclusive): `notifications.index`, `notifications.show`, `dashboard`.

#### Reviewer (`routes/web.php`, extra middleware `reviewer` = `users.is_reviewer`)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/reviews` | `Reviewer\SubmissionReviewController@index` | `reviews.index` |
| GET | `/reviews/{submissionReview}` | `SubmissionReviewController@show` | `reviews.show` |
| PUT | `/reviews/{submissionReview}` | `SubmissionReviewController@update` | `reviews.update` |
| GET | `/reviews/{submissionReview}/manuscript` | `SubmissionReviewController@download` | `reviews.manuscript` |

`Reviewer\SubmissionReviewController` is **not** registered in `routes/reviewer.php`.

#### Editor (`routes/admin.php`, middleware `editor` = `users.is_editor`)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/admin/submissions` | `Admin\SubmissionController@index` | `admin.submissions.index` |
| GET | `/admin/submissions/{submission}` | `Admin\SubmissionController@show` | `admin.submissions.show` |
| PUT | `/admin/submissions/{submission}` | `Admin\SubmissionController@update` | `admin.submissions.update` |
| GET | `/admin/submissions/{submission}/download` | `Admin\SubmissionController@download` | `admin.submissions.download` |
| GET | `/admin/submissions/{submission}/versions/{version}/download` | `Admin\SubmissionController@downloadVersion` | `admin.submissions.versions.download` |
| POST | `/admin/submissions/{submission}/convert` | `Admin\SubmissionController@convert` | `admin.submissions.convert` |
| POST | `/admin/submissions/{submission}/publish` | `Admin\SubmissionController@publish` | `admin.submissions.publish` |
| POST | `/admin/submissions/{submission}/reviews` | `Admin\SubmissionReviewAssignmentController@store` | `admin.submissions.reviews.store` |
| DELETE | `/admin/submissions/{submission}/reviews/{submissionReview}` | `SubmissionReviewAssignmentController@destroy` | `admin.submissions.reviews.destroy` |
| GET | `/admin/articles/create` | `Admin\ArticleController@create` | `admin.articles.create` |
| POST | `/admin/articles` | `Admin\ArticleController@store` | `admin.articles.store` |

#### Controllers (what they actually do)

| Class | Method | Behaviour |
|-------|--------|-----------|
| `App\Http\Controllers\SubmissionController` | `index` | Owner’s submissions + revision comments |
| | `create` / `store` | Intake; file via `SubmissionFileStore`; version 1; `SubmissionReceived` |
| | `show` | Owner-only; versions + author-facing comments |
| | `download` / `downloadVersion` | Stream current or historical file |
| | `storeRevision` | New file + version; status back to Submitted; increment `review_round` |
| `Admin\SubmissionController` | `index` / `show` | Editor queue and detail |
| | `update` | Any `SubmissionStatus`; notify on revision / accept / reject |
| | `convert` | Accepted → unpublished `Article` (`Accepted`); set `submissions.article_id` |
| | `publish` | Accepted → published `Article` + issue + optional PDF |
| `Admin\SubmissionReviewAssignmentController` | `store` / `destroy` | Create/remove `SubmissionReview` for current round |
| `Reviewer\SubmissionReviewController` | `index` / `show` / `update` / `download` | Blinded list, form, named download `manuscript.{ext}` |
| `Admin\ArticleController` | `create` / `store` | Direct publish (no Submission) |

#### Views

- `resources/views/submissions/create.blade.php`
- `resources/views/submissions/index.blade.php`
- `resources/views/submissions/show.blade.php`
- `resources/views/submissions/_revision-form.blade.php`
- `resources/views/admin/submissions/index.blade.php`
- `resources/views/admin/submissions/show.blade.php`
- `resources/views/reviews/index.blade.php`
- `resources/views/reviews/show.blade.php`
- `resources/views/admin/articles/create.blade.php`

#### Supporting types (portal-only)

- Models: `Submission`, `SubmissionReview`, `SubmissionVersion`
- Enums: `SubmissionStatus`, `SubmissionReviewStatus` (shared `ReviewRecommendation`)
- Requests: `StoreSubmissionRequest`, `StoreRevisionRequest`, `Admin\UpdateSubmissionRequest`, `Admin\AssignSubmissionReviewersRequest`, `Admin\PublishSubmissionRequest`, `Admin\StoreDirectArticleRequest`, `Reviewer\StoreSubmissionReviewRequest`
- Policies: `SubmissionPolicy`, `SubmissionReviewPolicy` (policy exists; `/reviews/*` uses `assertAssigned` instead)
- Notifications: `SubmissionNotification` + `SubmissionReceived`, `RevisionResubmitted`, `ReviewerAssigned`, `ReviewSubmitted`, `SubmissionDecisionMade`
- Support: `SubmissionFileStore`, `PublicationFileStore` (also used by direct publish)
- Config: `config/submissions.php`

---

### 1.2 Workspace

#### Author (`routes/author.php`, middleware `role:author`)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/author` | `Author\DashboardController` | `author.dashboard` |
| GET / PUT | `/author/profile` | `Author\ProfileController@edit/update` | `author.profile.*` |
| GET / POST | `/author/manuscripts/create`, `/author/manuscripts` | `Author\ManuscriptController@create/store` | `author.manuscripts.create/store` |
| GET | `/author/manuscripts/{manuscript:id}` | `ManuscriptController@show` | `author.manuscripts.show` |
| GET / PUT | `/author/manuscripts/{manuscript:id}/edit` | `edit` / `update` | `author.manuscripts.edit/update` |
| POST | `/author/manuscripts/{manuscript:id}/submit` | `submit` | `author.manuscripts.submit` |
| POST / GET / DELETE | `/author/manuscripts/{manuscript:id}/files…` | `Author\ManuscriptFileController` | `author.manuscripts.files.*` |

#### Editorial (`routes/editorial.php`, roles `journal_manager`, `editor_in_chief`, `section_editor`, `editor`, `copyeditor`)

**Manuscript lifecycle**

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/editorial` | `Editorial\DashboardController` | `editorial.dashboard` |
| GET / POST | `/editorial/notifications…` | `Editorial\NotificationController` | `editorial.notifications.*` |
| GET | `/editorial/manuscripts` | `Editorial\ManuscriptController@index` | `editorial.manuscripts.index` |
| GET | `/editorial/manuscripts/{manuscript:id}` | `show` | `editorial.manuscripts.show` |
| POST | `…/screen` | `screen` | `editorial.manuscripts.screen` |
| POST | `…/decision` | `decide` | `editorial.manuscripts.decision.store` |
| POST | `…/reviewers` | `assign` | `editorial.manuscripts.reviewers.store` |
| GET | `…/files/{file}` | `Editorial\ManuscriptFileController@download` | `editorial.manuscripts.files.download` |

**Volume / issue management (not manuscript review)**

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| resource | `/editorial/volumes` | `Editorial\VolumeController` | `editorial.volumes.*` (no show) |
| resource | `/editorial/issues` | `Editorial\IssueController` | `editorial.issues.*` |
| POST / PUT / DELETE | `/editorial/issues/{issue}/articles…` | `assign` / `updatePlacement` / `removePlacement` | `editorial.issues.articles.*` |
| PUT | `/editorial/issues/{issue}/order` | `reorder` | `editorial.issues.articles.reorder` |
| POST | `/editorial/issues/{issue}/publish` | `publish` | `editorial.issues.publish` |
| POST | `/editorial/issues/{issue}/unpublish` | `unpublish` | `editorial.issues.unpublish` |

#### Reviewer (`routes/reviewer.php`, middleware `role:reviewer`)

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/reviewer` | `Reviewer\DashboardController` | `reviewer.dashboard` |
| GET | `/reviewer/assignments/{assignment}` | `Reviewer\AssignmentController@show` | `reviewer.assignments.show` |
| POST | `…/accept` | `accept` | `reviewer.assignments.accept` |
| POST | `…/decline` | `decline` | `reviewer.assignments.decline` |
| POST | `…/review` | `storeReview` | `reviewer.assignments.review.store` |
| GET | `…/files/{file}` | `Reviewer\AssignmentFileController@download` | `reviewer.assignments.files.download` |

#### Workspace views

**Author:** `author/dashboard.blade.php`, `author/profile.blade.php`, `author/manuscripts/{create,edit,show}.blade.php`  
**Editorial:** `editorial/dashboard.blade.php`, `editorial/notifications/index.blade.php`, `editorial/manuscripts/{index,show}.blade.php`, `editorial/volumes/{index,create,edit}.blade.php`, `editorial/issues/{index,create,edit,show}.blade.php`  
**Reviewer:** `reviewer/dashboard.blade.php`, `reviewer/assignments/show.blade.php`  
**Nav components:** `components/author-nav.blade.php` (also links to `/submissions/*`), `components/editorial-nav.blade.php`, `components/reviewer-nav.blade.php`

#### Workspace supporting types

- Models: `Article` (manuscript methods), `Revision`, `ArticleFile`, `ReviewerAssignment`, `Review` (table `reviews`), `EditorialDecision`, `ArticleStatusEvent`, `JournalNotification`
- Enums: `ArticleStatus` (full OJS-style machine), `ArticleType`, `ArticleFileType`, `EditorialDecisionType`, `ReviewerAssignmentStatus`
- Policies: `ArticlePolicy` (author/editorial actions), `ArticleFilePolicy`, `ReviewerAssignmentPolicy`, plus `IssuePolicy` / `VolumePolicy` for editorial issues
- Requests under `app/Http/Requests/Author`, `Editorial`, `Reviewer` (`StoreReviewRequest`, `DeclineAssignmentRequest`)
- Notifications: `NewSubmissionNotification`, `RevisionSubmittedNotification`, `ReviewerInvitationNotification`, `ReviewerAcceptedNotification`, `ReviewerDeclinedNotification`, `ReviewSubmittedNotification`, `ReviewReminderNotification`, `RevisionRequestedNotification`, `ArticleAcceptedNotification`, `ArticleRejectedNotification`, `ArticlePublishedNotification`
- Support: `ManuscriptFileStore`, `IssuePublisher`, `Notifier`
- Command: `reviews:remind` (`SendReviewRemindersCommand`) — workspace assignments only

---

### 1.3 How users reach each system

| Surface | Portal | Workspace |
|---------|--------|-----------|
| `components/navigation.blade.php` | Submit / My submissions / Editorial submissions / Assigned reviews | **No links** |
| `pages/author-guidelines.blade.php` | `submissions.create` | None |
| `dashboard.blade.php` hub | Submit, Manuscript submissions, Assigned reviews | Editorial office, Author portal, Reviewer portal |
| `User::dashboardRoute()` after login | Never | Admin → admin; editorial roles → `/editorial`; role reviewer → `/reviewer`; role author → `/author` |
| `author-nav` | Submit + My submissions | Manuscripts + New manuscript + profile |

The **primary chrome already treats the portal as the product**. The workspace is still the **default landing page** for authors, editorial-role users, and role-reviewers.

Two different reviewer gates exist: `users.is_reviewer` (`/reviews`) vs `role:reviewer` (`/reviewer`). A user can have one and not the other.

---

## 2. Feature-by-feature comparison

Traced in controllers, not inferred from route names. “Works E2E” means the code path is complete and covered by a feature test.

| Feature | Portal | Workspace | Verdict |
|---------|--------|-----------|---------|
| **Submission intake** | `SubmissionController@store`: one PDF/DOCX, abstract, keywords, co-authors string. Status `Submitted`. | `Author\ManuscriptController@store` creates **Draft** `Article`; author uploads typed `ArticleFile`s; `submit` creates `Revision` and moves to `Submitted`. | **Both work E2E.** Portal is one-step. Workspace is draft-then-submit and richer (cover letter, declarations, multi-file). |
| **Reviewer assignment** | Editor picks `is_reviewer` users; `SubmissionReview` rows; first assign moves `Submitted` → `UnderReview`. | Editor assigns only if status is `UnderReview` **and** `currentRevision` exists; `ReviewerAssignment` + invitation mail; reviewer must accept/decline. | **Both work for their own records.** Workspace **errors** on portal/direct Articles (`This manuscript has no submitted revision to assign.`). |
| **Double-blind** | Reviewer query omits `user_id` / co-authors; UI withholds names; download filename forced to `manuscript.{ext}`; author sees “Comment N”. | Reviewer show omits authors; author sees `authorFacingReviews` / decisions without reviewer/editor names. | **Both implemented in UI.** Neither strips identifying metadata inside the file. Workspace reviewer file list is all manuscript/supplementary `ArticleFile`s on the article, not strictly the assigned revision. |
| **Decisions** | `Admin\SubmissionController@update` accepts any `SubmissionStatus`. Notify only when entering revision / accept / reject. No “reviews complete” gate. Manual `published` does **not** create an Article. | Screening (`screen`) then post-review (`decide`) via `EditorialDecision` + `ArticleStatus::allowedTransitions()`. Withdraw/schedule types exist in the enum but are not on the form. | **Both work** on their own status machines. They do **not** write each other’s tables. |
| **Revisions** | Author upload when `RevisionRequested`; `SubmissionVersion` history + download links. | New working files + `submit` while `RevisionRequired` → new `Revision`; old files frozen on prior revision. | **Both work E2E** on their own file tables. |
| **Notifications** | Mail + database via `SubmissionNotification` subclasses; inbox `notifications.*`. No author mail on publish. | `Notifier` + `JournalNotification` + mail; `reviews:remind`; author mail on issue publish via `IssuePublisher`. | **Both work** for their events. Two inboxes (`/notifications` vs `/editorial/notifications`). |
| **Publishing** | **Publish to Issue** (`admin.submissions.publish`) and **Add Article Directly** (`admin.articles.store`) set `ArticleStatus::Published`, `pdf_path`, issue pivot. **Convert** creates `Accepted` Article, no PDF. | `IssuePublisher` on `/editorial/issues`: place Accepted/Copyediting/Scheduled articles, publish/unpublish issue, flip `ArticleFile.is_public`. | **Both work** for records they created. They share `articles` / `issue_articles` and can overwrite each other’s publication state (see §5). |

### Portal gaps (real, but in-system)

- Editor can set status `published` without `publish()`, leaving no public Article.
- `convert` does not copy the manuscript onto `articles.pdf_path`.
- After a revision, status returns to `Submitted`; editors must re-assign the new round.
- `SubmissionReviewPolicy` is unused on `/reviews/*`.

### Workspace gaps vs portal-created Articles

- Reviewer assign **requires** a `Revision`.
- Default editorial queue **hides** `Accepted` / `Published` (those are the only statuses portal/direct write).
- `IssuePublisher::exposePublicPdf()` only looks at `ArticleFile`, not `pdf_path`. A convert-then-editorial-publish Article can go public **without a PDF**.

---

## 3. Test coverage

Suite size at last full run: **187 tests, 1668 assertions, 0 failures**.

### 3.1 Files that cover the submissions portal (25 dedicated tests)

| File | Tests |
|------|------:|
| `tests/Feature/Submissions/ManuscriptPortalTest.php` | 7 |
| `tests/Feature/Submissions/RevisionAndNotificationTest.php` | 3 |
| `tests/Feature/Submissions/SubmissionReviewWorkflowTest.php` | 7 |
| `tests/Feature/Admin/IssuePublicationTest.php` (Publish to Issue + admin issues) | 4 |
| `tests/Feature/Admin/DirectArticlePublishTest.php` | 4 |
| **Dedicated portal / admin-publish total** | **25** |

Also hits portal routes:

- `tests/Feature/Security/ProductionHardeningTest.php` — disguised-executable uploads on `submissions.store` / `submissions.revisions.store` (1 of 4 tests)

### 3.2 Files that cover the workspace (28 dedicated + 18 mixed)

| File | Tests | Notes |
|------|------:|-------|
| `tests/Feature/Author/ManuscriptSubmissionTest.php` | 11 | Dedicated |
| `tests/Feature/Editorial/RevisionDecisionWorkflowTest.php` | 5 | Dedicated |
| `tests/Feature/Editorial/PublicationWorkflowTest.php` | 5 | Dedicated (`IssuePublisher`) |
| `tests/Feature/PeerReview/PeerReviewWorkflowTest.php` | 7 | Dedicated |
| **Dedicated workspace total** | **28** | |
| `tests/Feature/NotificationStructureTest.php` | 7 | 4 manuscript + 2 editorial mail + 1 password |
| `tests/Feature/CompleteApplicationTest.php` | 6 | 2 full `/author`→`/editorial`→`/reviewer` workflows |
| `tests/Feature/Auth/AuthorizationTest.php` | 7 | All assert workspace dashboards |
| `tests/Feature/Security/SecurityAuditTest.php` | 14 | 5 use author files / reviewer assignments |
| `tests/Feature/Models/JournalDomainModelTest.php` | 9 | Relations include `ReviewerAssignment`, `Review`, `Revision` |
| `tests/Unit/Enums/ArticleStatusTest.php` | 3 | Workspace status machine |

Workspace-exercising tests (dedicated + clearly mixed): **28 + ~18 ≈ 46**.

### 3.3 Reading the counts

The workspace is **not** undertested. It has more dedicated workflow tests than the portal, plus the longest E2E (`CompleteApplicationTest::test_complete_editorial_workflow_from_submission_to_public_url`).

“Actively used / fully tested” in the original flag referred to **product direction and recent work**, not to a test-count deficit. Recent Phase 1 and “Add Article Directly” tests landed on the portal/admin path.

---

## 4. Real data (MySQL `journal_system`, 12 September 2026)

Seeded sample articles are marked `articles.is_demo = 1` in `JournalContentSeeder`. There is **no** `Submission` seeder. Workspace tables are not seeded.

| Table | Count | Notes |
|-------|------:|-------|
| `submissions` | **0** | No statuses |
| `submission_reviews` | **0** | |
| `submission_versions` | **0** | |
| `articles` | **3** | All `is_demo = 1`, status `published` |
| `articles` non-demo | **0** | |
| `articles` linked from `submissions.article_id` | **0** | |
| `revisions` | **0** | |
| `article_files` | **0** | |
| `reviewer_assignments` | **0** | |
| `reviews` (old article peer-review table) | **0** | |
| `editorial_decisions` | **0** | |
| `article_status_events` | **0** | |
| `journal_notifications` | table absent on this DB | |
| `users` | 3 | 1 `is_editor`, 0 `is_reviewer` |

The three articles are seeder samples (`sample-article-…` slugs, `corresponding_author_id = 2`, `pdf_path` null, `editor_id` null). They were **not** created by `/author/*`, `/admin/submissions/publish`, or `/admin/articles`.

**Answers to the required questions**

1. **Real (non-seed) Submissions:** **0.**
2. **Real records created through `/author/*`, `/editorial/*`, `/reviewer/*`:** **0.** No revisions, files, assignments, old `reviews`, or editorial decisions.
3. **Articles only the workspace created/modified:** **none.** The three demo rows have no `ReviewerAssignment` or `ArticleFile`. The Submission pipeline does not “know about” them either (no `submissions.article_id`). They are public demo content only.

There is **no data to migrate** on this database before removing either manuscript UI. Re-query production if this file is used against a different host.

---

## 5. Coupling after “Add Article Directly” and “Publish to Issue”

Neither admin writer creates workspace rows. They do **not** leave orphan `ReviewerAssignment` or `ArticleFile` records.

They **do** create `Article` rows that workspace screens treat as manuscripts.

### 5.1 What each writer writes

| Field / relation | Publish to Issue | Convert to Article | Add Article Directly | Workspace submit |
|------------------|------------------|--------------------|----------------------|------------------|
| `submissions.article_id` | Set | Set | None | None |
| `articles.submission_id` | Column does not exist | — | — | — |
| `status` | `published` | `accepted` | `published` | `submitted` (+ revision) |
| `corresponding_author_id` | Submission author | Submission author | **Acting editor** | Author |
| `pdf_path` | Optional upload | **Empty** | Required upload | Empty (file is `ArticleFile`) |
| `Revision` / `ArticleFile` / `ReviewerAssignment` | None | None | None | Created |
| `issue_articles` | Yes | No | Yes | Only after editorial place |
| `submission_number` | Auto on Article create | Auto | Auto | Auto |
| `is_demo` | false | false | false | false |

### 5.2 Does the workspace break, mislead, or show wrong data?

| Workspace surface | Direct-published Article | Publish-to-Issue Article | Convert-only Article | Seed demo (current DB) |
|-------------------|--------------------------|--------------------------|----------------------|------------------------|
| `/author` list (`Article::forAuthor`) | **Yes, on the editor’s author portal** if that user has role `author` (ownership is `corresponding_author_id`). Real named authors have no `article_authors.user_id`, so they do **not** see it. | **Yes, on the real author’s portal.** Status Published, **files_count = 0**, empty-looking workflow. | Same as publish, status Accepted. | **Yes, user 2** sees all 3 demos as “manuscripts”, 0 files. |
| `/author/manuscripts/{id}` | Renders. Empty files/revisions/decisions. Not editable (`Published` is not author-editable). | Same. | Same; still not a portal submission page. | Same. |
| `/editorial` dashboard counts | Ignored (not Submitted/UnderReview/…). | Ignored. | Ignored. | Ignored. |
| `/editorial/manuscripts` default | Hidden. | Hidden. | Hidden. | Hidden. |
| `/editorial/manuscripts/{id}` if opened | Loads. Assign reviewers **fails** (no revision). Peer-review history empty (portal reviews live in `submission_reviews`). | Same. | Same. | Same. |
| `/editorial/issues/{id}` assignable | Hidden (`whereDoesntHave('issues')` + already Published). | Hidden (already in an issue). | **Shown** (Accepted, no issue). Editorial publish via `IssuePublisher` can publish **without** a PDF. | Already in issues. |
| `/reviewer` | Empty unless someone hand-inserts `ReviewerAssignment`. | Empty. | Empty. | Empty. |

No 500s were found in these views for missing relations; the failure mode is **misleading lists and a hard error on assign**, not a crash.

### 5.3 Inconsistent / dangerous shared-table behaviour

1. **`IssuePublisher::unpublish()`** (editorial only) moves `Published` → `Scheduled` and only hides `ArticleFile`s. A portal/direct article in that issue **disappears from the public site** while `pdf_path` remains. Admin issue UI has no matching unpublish.
2. **`IssuePublisher::exposePublicPdf()`** ignores `pdf_path`. Mixing convert + editorial publish yields a public article with no download.
3. **Two issue UIs** write the same `issues` / `issue_articles` / `volumes` tables. Admin `VolumePlaceholderController` is list-only; real volume CRUD is editorial.
4. **Author dashboard** has no source filter. Published portal/direct/demo articles look like workspace manuscripts (`MS-YYYY-######` is generated for every Article in `Article::booted()`).
5. Workspace **never** updates `Submission` status. Portal **never** reads `reviews` / `ReviewerAssignment`.

### 5.4 Would deleting workspace routes/controllers break the portal?

**Manuscript review/intake: no.** Portal controllers do not call `Author\*`, `Editorial\ManuscriptController`, `Reviewer\AssignmentController`, or `IssuePublisher`.

**Must keep (shared):**

| Artifact | Why the kept system still needs it |
|----------|-------------------------------------|
| `Article`, `ArticleAuthor`, `Issue`, `Volume`, `IssueArticle` | Public site + admin publish/direct |
| `ArticlePolicy::view` / `publishDirectly` | Public + direct publish |
| `PublicationFileStore` | Portal publish + direct publish + public PDF |
| `ManuscriptFileStore` + `Article::publicPdfFile()` | Public `ArticleController@pdf` fallback when `pdf_path` is empty (workspace-published PDFs). Safe to keep the class even if no such rows exist today. |
| `CurrentJournal`, `Auditor`, form/icon/layout Blade | Shared chrome |
| `IssuePolicy` / `VolumePolicy` | Admin issues; editorial volumes if kept |
| `reviews` table / `Review` model | Leave schema; do not drop until a later cleanup. Portal uses `submission_reviews`. |

**Would break if deleted carelessly:**

- `User::dashboardRoute()` → `author.dashboard` / `editorial.dashboard` / `reviewer.dashboard`
- Registration test expects redirect to `author.dashboard`
- `author-nav` links (only if author layout remains)
- `require` of `author.php` / `editorial.php` / `reviewer.php` in `web.php`
- ~46 tests listed in §3.2
- **Editorial volume/issue UI** — admin cannot fully replace it today (`admin/volumes` is a placeholder)

Deleting workspace **views/controllers only** does not require a table drop. Shared migrations stay.

---

## 6. Effort to remove the unused manuscript system

**Which system is unused as a manuscript pipeline:** the workspace (`/author` manuscripts, `/editorial/manuscripts`, `/reviewer` assignments).  
**Which system is the product pipeline:** submissions portal + admin Publish to Issue + Add Article Directly.

Live data migration: **none** on `journal_system` (zero real manuscript rows). On any other environment, archive then delete:

```sql
-- inspect first; do not run blindly
SELECT id, status FROM submissions;
SELECT id, status, is_demo FROM articles WHERE is_demo = 0;
SELECT article_id FROM revisions;
SELECT article_id FROM article_files;
SELECT article_id FROM reviewer_assignments;
```

If a non-demo Article has revisions/files/assignments and **no** `submissions.article_id`, it is workspace-origin and needs a one-off export (or a Submission row) before UI removal.

### Estimate

| Step | Effort | Notes |
|------|--------|-------|
| Hide workspace from UX (dashboard cards, `dashboardRoute`, author-nav) | 0.5 day | Lowest risk; can ship first |
| Delete manuscript workspace code + retarget tests | 2–3 days | See plan below |
| Keep or migrate `/editorial/volumes` + `/editorial/issues` | +0 or +1–2 days | **Do not delete this in the same PR** unless admin volume/issue CRUD is finished |
| Drop unused tables (`revisions`, `article_files`, `reviewer_assignments`, `reviews`, `editorial_decisions`) | +0.5 day later | Optional; only after no fallback PDF depends on `article_files` |
| **Total to a single manuscript UI** | **~3 days** (keep editorial issues) or **~5 days** (move issues to admin first) | |

---

## Recommendation

**Keep the submissions portal (`/submissions`, `/admin/submissions`, `/reviews`) plus the two admin Article writers (Publish to Issue, Add Article Directly) as the only manuscript source of truth. Remove the workspace manuscript lifecycle (`/author/manuscripts`, `/editorial/manuscripts`, `/reviewer/assignments`).**

That is not because the workspace is incomplete — it is the richer OJS-style product and has more dedicated tests — but because (1) the live database has zero workspace manuscripts and zero portal submissions, so there is no operational history to preserve; (2) every user-facing entry point added since the first flag (site nav, author guidelines, admin queue, `/reviews`, version downloads, direct publish) writes the portal or admin Article path; (3) those new Article writers already leak into `/author` and `/editorial/issues` in misleading or unsafe ways (`corresponding_author_id` = editor on direct publish; convert-only rows assignable without a PDF; `IssuePublisher::unpublish` can unpublish portal articles). Two writers on `articles` will keep drifting. The workspace should not remain a second writer.

Do **not** delete all of `/editorial` in the same change: admin volume management is still a placeholder, and `IssuePublisher` is the only issue unpublish/reorder implementation. Keep `/editorial/volumes` and `/editorial/issues` until that work lives under `/admin`, or accept a short period where editorial staff manage issues there but **never** manuscripts.

---

## Removal plan (do not execute in this task)

Prerequisite: re-run the §4 queries on the target environment. If counts are still zero, skip data migration.

### Step 0 — Stop the leak (can ship alone)

1. Change `User::dashboardRoute()` so authors land on `submissions.index` (or `dashboard`), editorial `is_editor` users on `admin.submissions.index`, `is_reviewer` users on `reviews.index`. Keep `editorial.dashboard` only if volume/issue UI stays.
2. Remove workspace cards from `resources/views/dashboard.blade.php` (Author portal, Reviewer portal; Editorial office only if issues move).
3. Remove portal links from `components/author-nav.blade.php` or delete that nav once author pages go.
4. Stop advertising two reviewer portals.

### Step 1 — Data (only if §4 is no longer empty)

1. Export workspace-only Articles (`revisions` or `article_files` exist, no matching `submissions.article_id`).
2. Decide per row: discard, or create a `Submission` + `SubmissionVersion` pointing at a copied file and set `article_id` if already published.
3. Do not delete seed `is_demo` articles; they are public fixtures, not workspace manuscripts.

### Step 2 — Extract / keep shared code before deleting

Leave in place: `Article` and publication relations, `PublicationFileStore`, `ManuscriptFileStore`, `Article::publicPdfFile()` / `hasDownloadablePdf()`, public `ArticleController`, admin issue/article controllers, `CurrentJournal`, `Auditor`, shared Blade components, `IssuePolicy`, `VolumePolicy`.

If `/editorial/issues` stays: also keep `IssuePublisher`, `Editorial\IssueController`, `Editorial\VolumeController`, their requests and views.

### Step 3 — Delete manuscript workspace surface

**Routes**

- Remove manuscript routes from `routes/author.php` (keep file only if profile stays; otherwise delete the file and the `require` in `web.php`).
- Remove manuscript/notification routes from `routes/editorial.php`; keep volume/issue routes if Step 2 said so.
- Delete `routes/reviewer.php` and its `require` (portal reviewer stays in `web.php`).

**Controllers to delete**

- `app/Http/Controllers/Author/ManuscriptController.php`
- `app/Http/Controllers/Author/ManuscriptFileController.php`
- `app/Http/Controllers/Author/DashboardController.php`
- `app/Http/Controllers/Author/ProfileController.php` (duplicate of `/profile`; delete unless a unique field remains)
- `app/Http/Controllers/Editorial/ManuscriptController.php`
- `app/Http/Controllers/Editorial/ManuscriptFileController.php`
- `app/Http/Controllers/Editorial/DashboardController.php` (rewrite to issues-only if editorial issues stay)
- `app/Http/Controllers/Editorial/NotificationController.php` (optional; portal uses `/notifications`)
- `app/Http/Controllers/Reviewer/DashboardController.php`
- `app/Http/Controllers/Reviewer/AssignmentController.php`
- `app/Http/Controllers/Reviewer/AssignmentFileController.php`  
  **Do not delete** `Reviewer\SubmissionReviewController`.

**Views to delete**

- `resources/views/author/**`
- `resources/views/editorial/manuscripts/**`
- `resources/views/editorial/dashboard.blade.php` (or replace)
- `resources/views/editorial/notifications/**` (if inbox removed)
- `resources/views/reviewer/**`
- `resources/views/components/author-nav.blade.php`
- `resources/views/components/reviewer-nav.blade.php`
- Trim `editorial-nav` to volumes/issues if those routes stay

**Requests / policies / notifications / command**

- `app/Http/Requests/Author/*`
- Editorial: `ScreenManuscriptRequest`, `StoreEditorialDecisionRequest`, `AssignReviewerRequest` (keep issue/volume requests)
- `app/Http/Requests/Reviewer/StoreReviewRequest.php`, `DeclineAssignmentRequest.php` (keep `StoreSubmissionReviewRequest`)
- `ArticleFilePolicy`, `ReviewerAssignmentPolicy` (after no remaining download routes)
- Workspace-only notifications listed in §1.2
- `app/Console/Commands/SendReviewRemindersCommand.php` and its console registration

**Tests to delete or rewrite**

- Delete: `Author/ManuscriptSubmissionTest`, `Editorial/RevisionDecisionWorkflowTest`, `PeerReview/PeerReviewWorkflowTest`
- Keep but retarget: `Editorial/PublicationWorkflowTest` if issues stay (against remaining issue routes)
- Rewrite: `CompleteApplicationTest` workflow tests → portal path (`submissions` → `admin.submissions` → `reviews` → publish)
- Rewrite: `AuthorizationTest`, `NotificationStructureTest`, workspace cases in `SecurityAuditTest`
- Keep: `JournalDomainModelTest` (trim assignment/review assertions only when tables drop)
- Keep all `tests/Feature/Submissions/*`, `DirectArticlePublishTest`, `IssuePublicationTest`

### Step 4 — Do not do yet

- Do not drop `articles` columns used as manuscripts (`submission_number`, `cover_letter`, …) until a later schema cleanup.
- Do not drop `reviews` vs `submission_reviews` in the same PR as the UI removal.
- Do not merge the two reviewer flags (`is_reviewer` vs role) in the same PR.
- Do not implement a sync layer. One writer: portal + the two admin Article actions.

### Step 5 — Verify

1. `php artisan test` green.
2. Manual: `/submissions/create`, `/admin/submissions`, `/reviews`, `/admin/articles/create`, `/about` still 200.
3. Confirm `/author/manuscripts/create` and `/reviewer` 404 (or redirect).
4. Confirm a direct-published article does **not** appear on any author manuscript list (list should be gone).
5. If editorial issues remain: unpublish an issue that contains a portal-published article and confirm the behaviour is documented or blocked (today it would unpublish those articles).

---

## If this recommendation is rejected

If both UIs must remain visible, they still must **not** both write manuscript state. Evidence against “both stay as equals”: zero real usage of either pipeline, and §5 already shows incorrect author lists and issue-publish edge cases.

The only safe dual-UI rule:

1. **`Submission` is the only manuscript writer** (intake, review, decision, revision).
2. **`Article` is the only publication record**, written only by `Admin\SubmissionController@convert|publish` and `Admin\ArticleController@store`.
3. Workspace routes become **read-only views** of those Articles, or are hidden. Workspace must not call `Article::moveTo`, `ReviewerAssignment::create`, or `IssuePublisher::unpublish` on rows it did not create.
4. `Article::scopeForAuthor` must exclude `Published` / `Accepted` rows that have no `Revision` (portal/direct/demo), so authors are not shown empty “manuscripts”.
5. Editorial assignable list must require a workspace `Revision` **or** an existing `pdf_path`, never convert-only empty Articles.

That adapter work is larger than removal given empty tables. Prefer removal.
