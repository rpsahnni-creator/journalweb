<?php

namespace Database\Seeders;

use App\Enums\JournalPolicyType;
use App\Models\EditorialBoardMember;
use App\Models\Journal;
use App\Models\JournalPolicy;
use App\Models\JournalSetting;
use App\Support\JournalCopy;
use Illuminate\Database\Seeder;

class JournalIdentitySeeder extends Seeder
{
    public function run(): void
    {
        $journal = Journal::query()->updateOrCreate(
            ['slug' => 'academic-journal'],
            [
                'name' => 'SRT Journal of Multidisciplinary Research',
                'abbreviation' => 'SRT-JMR',
                'description' => 'A peer-reviewed, refereed academic journal published by Sri Raghunandan Tiwari College (S.R.T. College), Dhamni.',
                'issn' => null,
                'eissn' => null,
                'publisher' => JournalCopy::PUBLISHER_NAME,
                'website_url' => null,
                'is_active' => true,
            ]
        );

        $settings = [
            'issn' => null,
            'contact_email' => JournalCopy::PUBLISHER_EMAIL,
            'contact_address' => JournalCopy::PUBLISHER_ADDRESS,
            'seo_description' => 'Published research, issues, and journal policies. Unpublished manuscripts are not displayed.',
            'about_text' => JournalCopy::ABOUT_TEXT,
            'aims_scope_text' => JournalCopy::AIMS_SCOPE_TEXT,
            'publisher_name' => JournalCopy::PUBLISHER_NAME,
            'publisher_address' => JournalCopy::PUBLISHER_ADDRESS,
            'publisher_email' => JournalCopy::PUBLISHER_EMAIL,
            'publisher_website' => JournalCopy::PUBLISHER_WEBSITE,
            'publication_frequency' => JournalCopy::PUBLICATION_FREQUENCY,
            'indexing_status' => JournalCopy::INDEXING_STATUS,
        ];

        foreach ($settings as $key => $value) {
            JournalSetting::query()->updateOrCreate(
                ['journal_id' => $journal->id, 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ([
            JournalPolicyType::About->value => JournalCopy::ABOUT_TEXT,
            JournalPolicyType::AimsAndScope->value => JournalCopy::AIMS_SCOPE_TEXT,
            JournalPolicyType::Plagiarism->value => JournalCopy::PLAGIARISM_POLICY,
            JournalPolicyType::Apc->value => JournalCopy::APC_POLICY,
            JournalPolicyType::ConflictOfInterest->value => JournalCopy::CONFLICT_OF_INTEREST_POLICY,
            JournalPolicyType::CorrectionsAndRetractions->value => JournalCopy::CORRECTIONS_AND_RETRACTIONS_POLICY,
            JournalPolicyType::ComplaintsAndAppeals->value => JournalCopy::COMPLAINTS_AND_APPEALS_POLICY,
            JournalPolicyType::ReviewerGuidelines->value => JournalCopy::REVIEWER_GUIDELINES,
            JournalPolicyType::ReviewProcess->value => JournalCopy::REVIEW_PROCESS,
            JournalPolicyType::SubmissionChecklist->value => JournalCopy::submissionChecklistBody(),
            JournalPolicyType::ManuscriptPreparation->value => JournalCopy::manuscriptPreparationBody(),
        ] as $type => $body) {
            $policyType = JournalPolicyType::from($type);

            JournalPolicy::query()->updateOrCreate(
                ['journal_id' => $journal->id, 'type' => $policyType->value],
                [
                    'title' => $policyType->title(),
                    'slug' => str_replace('_', '-', $policyType->value),
                    'body' => $body,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }

        EditorialBoardMember::syncRoster($journal);
    }
}
