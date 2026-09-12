<?php

namespace Tests\Unit\Enums;

use App\Enums\ArticleStatus;
use PHPUnit\Framework\TestCase;

class ArticleStatusTest extends TestCase
{
    public function test_it_defines_the_complete_workflow_status_list(): void
    {
        $this->assertSame([
            'draft',
            'submitted',
            'initial_screening',
            'under_review',
            'revision_required',
            'resubmitted',
            'accepted',
            'rejected',
            'copyediting',
            'scheduled',
            'published',
            'withdrawn',
        ], ArticleStatus::values());
    }

    public function test_only_published_articles_are_publicly_visible(): void
    {
        $this->assertTrue(ArticleStatus::Published->isPubliclyVisible());
        $this->assertFalse(ArticleStatus::Draft->isPubliclyVisible());
        $this->assertFalse(ArticleStatus::Accepted->isPubliclyVisible());
        $this->assertFalse(ArticleStatus::UnderReview->isPubliclyVisible());
        $this->assertTrue(ArticleStatus::Draft->isAuthorEditable());
        $this->assertTrue(ArticleStatus::RevisionRequired->isAuthorEditable());
        $this->assertFalse(ArticleStatus::Submitted->isAuthorEditable());
        $this->assertFalse(ArticleStatus::UnderReview->isAuthorEditable());
    }

    public function test_it_allows_only_valid_workflow_transitions(): void
    {
        $this->assertTrue(ArticleStatus::Draft->canTransitionTo(ArticleStatus::Submitted));
        $this->assertTrue(ArticleStatus::Submitted->canTransitionTo(ArticleStatus::UnderReview));
        $this->assertTrue(ArticleStatus::UnderReview->canTransitionTo(ArticleStatus::RevisionRequired));
        $this->assertTrue(ArticleStatus::RevisionRequired->canTransitionTo(ArticleStatus::Resubmitted));
        $this->assertTrue(ArticleStatus::Resubmitted->canTransitionTo(ArticleStatus::Accepted));
        $this->assertTrue(ArticleStatus::Resubmitted->canTransitionTo(ArticleStatus::RevisionRequired));
        $this->assertTrue(ArticleStatus::UnderReview->canReceiveEditorialDecision());
        $this->assertTrue(ArticleStatus::Resubmitted->canReceiveEditorialDecision());
        $this->assertFalse(ArticleStatus::Submitted->canReceiveEditorialDecision());
        $this->assertFalse(ArticleStatus::Draft->canTransitionTo(ArticleStatus::Accepted));
        $this->assertFalse(ArticleStatus::UnderReview->canTransitionTo(ArticleStatus::Submitted));
        $this->assertFalse(ArticleStatus::Accepted->canTransitionTo(ArticleStatus::RevisionRequired));
        $this->assertFalse(ArticleStatus::Rejected->canTransitionTo(ArticleStatus::UnderReview));
        $this->assertTrue(ArticleStatus::Accepted->canTransitionTo(ArticleStatus::Scheduled));
        $this->assertTrue(ArticleStatus::Accepted->canTransitionTo(ArticleStatus::Published));
        $this->assertTrue(ArticleStatus::Copyediting->canTransitionTo(ArticleStatus::Published));
        $this->assertTrue(ArticleStatus::Scheduled->canTransitionTo(ArticleStatus::Published));
        $this->assertTrue(ArticleStatus::Published->canTransitionTo(ArticleStatus::Scheduled));
        $this->assertTrue(ArticleStatus::Accepted->canBeScheduledForPublication());
        $this->assertFalse(ArticleStatus::UnderReview->canBeScheduledForPublication());
    }
}
