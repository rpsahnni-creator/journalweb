<?php

namespace App\Http\Controllers;

use App\Support\CurrentJournal;
use Illuminate\Contracts\View\View;

class EditorialBoardController extends Controller
{
    public function __invoke(): View
    {
        $journal = CurrentJournal::get();

        $members = $journal
            ? $journal->editorialBoardMembers()->public()->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('pages.editorial-board', [
            'journal' => $journal,
            'members' => $members->groupBy('role_title'),
            'title' => 'Editorial Board',
            'metaDescription' => 'Public editorial board listings. Members appear only after they are added and marked public.',
        ]);
    }
}
