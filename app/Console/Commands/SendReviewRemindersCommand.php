<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\ReviewerAssignmentStatus;
use App\Models\JournalNotification;
use App\Models\ReviewerAssignment;
use App\Notifications\ReviewReminderNotification;
use App\Support\Notifier;
use Illuminate\Console\Command;

class SendReviewRemindersCommand extends Command
{
    protected $signature = 'reviews:remind';

    protected $description = 'Email reviewers whose invitations or accepted reviews are due or overdue.';

    public function handle(): int
    {
        $assignments = ReviewerAssignment::query()
            ->with(['article', 'reviewer'])
            ->whereIn('status', [
                ReviewerAssignmentStatus::Invited,
                ReviewerAssignmentStatus::Accepted,
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays(2))
            ->get();

        $sent = 0;

        foreach ($assignments as $assignment) {
            if ($assignment->reviewer === null || $assignment->article === null) {
                continue;
            }

            $recent = JournalNotification::query()
                ->where('user_id', $assignment->reviewer_id)
                ->where('type', NotificationType::ReviewReminder->value)
                ->where('related_type', $assignment::class)
                ->where('related_id', $assignment->id)
                ->where('created_at', '>=', now()->subDays(3))
                ->exists();

            if ($recent) {
                continue;
            }

            Notifier::notify(
                $assignment->reviewer,
                new ReviewReminderNotification(
                    $assignment,
                    route('reviewer.assignments.show', $assignment)
                )
            );
            $sent++;
        }

        $this->info("Sent {$sent} review reminder".($sent === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
