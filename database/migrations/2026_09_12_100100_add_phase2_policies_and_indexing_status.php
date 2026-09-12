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
            $hasIndexing = DB::table('journal_settings')
                ->where('journal_id', $journal->id)
                ->where('key', 'indexing_status')
                ->exists();

            if (! $hasIndexing) {
                DB::table('journal_settings')->insert([
                    'journal_id' => $journal->id,
                    'key' => 'indexing_status',
                    'value' => JournalCopy::INDEXING_STATUS,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($this->policies() as $type => $body) {
                $policyType = JournalPolicyType::from($type);
                $exists = DB::table('policies')
                    ->where('journal_id', $journal->id)
                    ->where('type', $policyType->value)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('policies')->insert([
                    'journal_id' => $journal->id,
                    'type' => $policyType->value,
                    'title' => $policyType->title(),
                    'slug' => str_replace('_', '-', $policyType->value),
                    'body' => $body,
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
        DB::table('journal_settings')->where('key', 'indexing_status')->delete();
        DB::table('policies')->whereIn('type', array_keys($this->policies()))->delete();
    }

    /**
     * @return array<string, string>
     */
    private function policies(): array
    {
        return [
            JournalPolicyType::ConflictOfInterest->value => JournalCopy::CONFLICT_OF_INTEREST_POLICY,
            JournalPolicyType::CorrectionsAndRetractions->value => JournalCopy::CORRECTIONS_AND_RETRACTIONS_POLICY,
            JournalPolicyType::ComplaintsAndAppeals->value => JournalCopy::COMPLAINTS_AND_APPEALS_POLICY,
            JournalPolicyType::ReviewerGuidelines->value => JournalCopy::REVIEWER_GUIDELINES,
            JournalPolicyType::ReviewProcess->value => JournalCopy::REVIEW_PROCESS,
            JournalPolicyType::SubmissionChecklist->value => JournalCopy::submissionChecklistBody(),
            JournalPolicyType::ManuscriptPreparation->value => JournalCopy::manuscriptPreparationBody(),
        ];
    }
};
