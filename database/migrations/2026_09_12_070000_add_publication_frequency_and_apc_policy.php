<?php

use App\Enums\JournalPolicyType;
use App\Support\JournalCopy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (DB::table('journals')->orderBy('id')->get(['id']) as $journal) {
            $hasFrequency = DB::table('journal_settings')
                ->where('journal_id', $journal->id)
                ->where('key', 'publication_frequency')
                ->exists();

            if (! $hasFrequency) {
                DB::table('journal_settings')->insert([
                    'journal_id' => $journal->id,
                    'key' => 'publication_frequency',
                    'value' => JournalCopy::PUBLICATION_FREQUENCY,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $hasApcPolicy = DB::table('policies')
                ->where('journal_id', $journal->id)
                ->where('type', JournalPolicyType::Apc->value)
                ->exists();

            if (! $hasApcPolicy) {
                DB::table('policies')->insert([
                    'journal_id' => $journal->id,
                    'type' => JournalPolicyType::Apc->value,
                    'title' => JournalPolicyType::Apc->title(),
                    'slug' => 'article-processing-charges',
                    'body' => JournalCopy::APC_POLICY,
                    'is_published' => true,
                    'published_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('journal_settings')->where('key', 'publication_frequency')->delete();
        DB::table('policies')->where('type', JournalPolicyType::Apc->value)->delete();
    }
};
