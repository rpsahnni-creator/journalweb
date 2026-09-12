<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Support\CurrentJournal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function create(): View
    {
        $journal = CurrentJournal::get();

        return view('pages.contact', [
            'journal' => $journal,
            'title' => 'Contact',
            'metaDescription' => 'Contact the journal editorial office. Do not send unpublished manuscripts through this form.',
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::get();

        ContactMessage::query()->create([
            ...$request->validated(),
            'journal_id' => $journal?->id,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Thank you. Your message has been received.');
    }
}
