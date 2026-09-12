<?php

namespace Tests\Unit\Support;

use App\Services\JournalChatbot;
use Tests\TestCase;

class JournalChatbotTest extends TestCase
{
    public function test_it_does_not_treat_this_as_a_greeting(): void
    {
        $reply = (new JournalChatbot)->reply('Tell me about this journal');

        $this->assertStringContainsString('peer-reviewed', $reply['reply']);
        $this->assertNotSame((new JournalChatbot)->greeting(), $reply['reply']);
    }

    public function test_it_matches_hindi_submission_questions(): void
    {
        $reply = (new JournalChatbot)->reply('पांडुलिपि कैसे जमा करें?', 'hi');

        $this->assertStringContainsString('पांडुलिपि', $reply['reply']);
        $this->assertStringContainsString('docx', $reply['reply']);
    }

    public function test_unknown_questions_get_a_fallback_and_contact_link(): void
    {
        $reply = (new JournalChatbot)->reply('What is the weather in Godda?');

        $this->assertStringContainsString('suggested questions', $reply['reply']);
        $this->assertNotEmpty($reply['links']);
    }
}
