<?php

namespace App\Http\Controllers\Reviewer;

use App\Enums\ReviewerAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('access-reviewer');

        $assignments = ReviewerAssignment::query()
            ->with(['article', 'review'])
            ->where('reviewer_id', $request->user()->id)
            ->latest('invited_at')
            ->get();

        return view('reviewer.dashboard', [
            'user' => $request->user(),
            'invitations' => $assignments->filter(fn (ReviewerAssignment $assignment) => $assignment->status === ReviewerAssignmentStatus::Invited),
            'inProgress' => $assignments->filter(fn (ReviewerAssignment $assignment) => $assignment->status === ReviewerAssignmentStatus::Accepted),
            'completed' => $assignments->filter(fn (ReviewerAssignment $assignment) => $assignment->status === ReviewerAssignmentStatus::Completed),
        ]);
    }
}
