<?php

use App\Support\JournalCopy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('journals')->orderBy('id')->get(['id']) as $journal) {
            $hasWebsite = DB::table('journal_settings')
                ->where('journal_id', $journal->id)
                ->where('key', 'publisher_website')
                ->exists();

            if (! $hasWebsite) {
                DB::table('journal_settings')->insert([
                    'journal_id' => $journal->id,
                    'key' => 'publisher_website',
                    'value' => JournalCopy::PUBLISHER_WEBSITE,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('journal_settings')->where('key', 'publisher_website')->delete();
    }
};
