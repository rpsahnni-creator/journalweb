<?php

use App\Support\JournalCopy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $journals = DB::table('journals')->orderBy('id')->get(['id']);

        foreach ($journals as $journal) {
            foreach (JournalCopy::editorialBoardRoster() as $member) {
                $match = $member['sort_order'] === 1
                    ? DB::table('editorial_board_members')
                        ->where('journal_id', $journal->id)
                        ->where('email', $member['email'])
                        ->first()
                    : DB::table('editorial_board_members')
                        ->where('journal_id', $journal->id)
                        ->where(function ($query) use ($member): void {
                            $query->where('sort_order', $member['sort_order'])
                                ->orWhere('name', $member['name']);
                        })
                        ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$member['name']])
                        ->orderBy('sort_order')
                        ->first();

                $payload = [
                    'name' => $member['name'],
                    'role_title' => $member['role_title'],
                    'department' => $member['department'],
                    'affiliation' => $member['affiliation'],
                    'official_address' => $member['official_address'],
                    'email' => $member['email'],
                    'sort_order' => $member['sort_order'],
                    'is_public' => $member['is_public'] ? 1 : 0,
                    'is_active' => 1,
                    'updated_at' => now(),
                ];

                if ($match) {
                    DB::table('editorial_board_members')->where('id', $match->id)->update($payload);

                    continue;
                }

                DB::table('editorial_board_members')->insert([
                    ...$payload,
                    'journal_id' => $journal->id,
                    'created_at' => now(),
                ]);
            }

            DB::table('editorial_board_members')
                ->where('journal_id', $journal->id)
                ->where('name', JournalCopy::PLACEHOLDER_BOARD_NAME)
                ->delete();
        }
    }

    public function down(): void
    {
        $journals = DB::table('journals')->orderBy('id')->get(['id']);

        foreach ($journals as $journal) {
            $replacements = collect(JournalCopy::editorialBoardRoster())
                ->where('sort_order', '>', 1)
                ->values();

            foreach ($replacements as $member) {
                DB::table('editorial_board_members')
                    ->where('journal_id', $journal->id)
                    ->where('name', $member['name'])
                    ->update([
                        'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,
                        'role_title' => JournalCopy::PLACEHOLDER_BOARD_DESIGNATION,
                        'department' => null,
                        'affiliation' => JournalCopy::PLACEHOLDER_BOARD_INSTITUTION,
                        'official_address' => null,
                        'email' => JournalCopy::PLACEHOLDER_BOARD_EMAIL,
                        'is_public' => 0,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
};
