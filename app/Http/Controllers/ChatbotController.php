<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatbotMessageRequest;
use App\Services\JournalChatbot;
use Illuminate\Http\JsonResponse;

class ChatbotController extends Controller
{
    public function __invoke(StoreChatbotMessageRequest $request, JournalChatbot $chatbot): JsonResponse
    {
        $payload = $chatbot->reply(
            $request->validated('message'),
            app()->getLocale() === 'hi' ? 'hi' : 'en',
        );

        return response()->json($payload);
    }
}
