<?php

namespace Tests\Feature;

use App\Services\JournalChatbot;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_includes_the_chatbot_widget(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('ui.chatbot_title'), false)
            ->assertSee('journal-chatbot-input', false);
    }

    public function test_chatbot_answers_submission_and_fee_questions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->postJson(route('chatbot'), ['message' => 'How do I submit a manuscript?'])
            ->assertOk()
            ->assertJsonPath('reply', fn (string $reply): bool => str_contains($reply, 'Submit Manuscript') || str_contains($reply, 'docx'))
            ->assertJsonStructure(['reply', 'links', 'suggestions']);

        $this->postJson(route('chatbot'), ['message' => 'Is there a publication fee or APC?'])
            ->assertOk()
            ->assertJsonFragment(['reply' => (new JournalChatbot)->reply('Is there a publication fee or APC?')['reply']]);
    }

    public function test_chatbot_replies_in_hindi_when_the_locale_is_hindi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $reply = $this->withSession(['locale' => 'hi'])
            ->postJson(route('chatbot'), ['message' => 'पांडुलिपि कैसे जमा करें?'])
            ->assertOk()
            ->json('reply');

        $this->assertIsString($reply);
        $this->assertStringContainsString('पांडुलिपि', $reply);
    }

    public function test_chatbot_rejects_an_empty_message(): void
    {
        $this->postJson(route('chatbot'), ['message' => ''])
            ->assertUnprocessable();
    }
}
