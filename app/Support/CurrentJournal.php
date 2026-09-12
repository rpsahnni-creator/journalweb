<?php

namespace App\Support;

use App\Models\Journal;

class CurrentJournal
{
    public static function get(): ?Journal
    {
        return once(static fn (): ?Journal => Journal::query()->active()->orderBy('id')->first());
    }

    public static function managed(): ?Journal
    {
        return once(static fn (): ?Journal => Journal::query()->orderBy('id')->first());
    }
}
