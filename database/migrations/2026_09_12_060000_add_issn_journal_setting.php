<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('journals')->orderBy('id')->get(['id', 'issn']) as $journal) {
            $exists = DB::table('journal_settings')
                ->where('journal_id', $journal->id)
                ->where('key', 'issn')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('journal_settings')->insert([
                'journal_id' => $journal->id,
                'key' => 'issn',
                'value' => $journal->issn,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('journal_settings')->where('key', 'issn')->delete();
    }
};
