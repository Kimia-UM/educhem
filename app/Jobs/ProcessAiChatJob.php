<?php

namespace App\Jobs;

use App\Ai\Agents\ChemistryTutorAgent;
use App\Models\AiChatLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Cache;

class ProcessAiChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 10;

    public function __construct(
        public AiChatLog $chatLog,
        public string $topicContext = '',
        public ?string $chatbotPrompt = null,
    ) {}

    public function handle(): void
    {
        $cacheKey = 'ai_chat_v1:' . md5($this->topicContext . ':' . ($this->chatbotPrompt ?? '') . ':' . trim(mb_strtolower($this->chatLog->prompt)));

        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse) {
            $this->chatLog->update([
                'response' => $cachedResponse,
            ]);
            return;
        }

        try {
            $response = (new ChemistryTutorAgent($this->topicContext, $this->chatbotPrompt))
                ->prompt($this->chatLog->prompt);

            if ($response) {
                $responseStr = (string) $response;
                Cache::put($cacheKey, $responseStr, now()->addDays(3));

                $this->chatLog->update([
                    'response' => $responseStr,
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, '429') || str_contains($errorMessage, '503') || str_contains($errorMessage, 'overloaded') || str_contains($errorMessage, 'quota')) {
                Log::warning('Gemini rate limit tercapai saat Chatbot berjalan. Menunda antrean 15 detik...');
                $this->release(15);
                return;
            }

            Log::error('ChemistryTutorAgent Exception: '.$errorMessage);
            throw $e;
        }
    }
}