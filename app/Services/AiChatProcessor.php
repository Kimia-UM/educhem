<?php

namespace App\Services;

use App\Ai\Agents\ChemistryTutorAgent;
use App\Models\AiChatLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use RuntimeException;

class AiChatProcessor
{
    public function process(
        AiChatLog $chatLog,
        string $topicContext,
        ?string $chatbotPrompt,
        string $mode,
        int $timeout,
    ): string {
        $processingStartedAtMs = self::nowMilliseconds();
        $enqueuedAtMs = $this->enqueuedAtMilliseconds($chatLog);

        $chatLog->forceFill([
            'status' => AiChatLog::STATUS_PROCESSING,
            'error_code' => null,
            'processing_mode' => $mode,
            'processing_started_at' => now(),
            'queue_wait_ms' => max(0, $processingStartedAtMs - $enqueuedAtMs),
        ])->save();

        $history = $this->conversationHistory($chatLog);
        $cacheKey = self::cacheKey($topicContext, $chatbotPrompt, $chatLog->prompt);
        $cachedResponse = $history === [] ? Cache::get($cacheKey) : null;

        if (is_string($cachedResponse) && $cachedResponse !== '') {
            $this->complete($chatLog, $cachedResponse, $enqueuedAtMs, 0);

            return $cachedResponse;
        }

        $providerStartedAtMs = self::nowMilliseconds();
        $response = (new ChemistryTutorAgent($topicContext, $chatbotPrompt, $history))
            ->prompt($chatLog->prompt, timeout: $timeout);
        $providerDurationMs = max(0, self::nowMilliseconds() - $providerStartedAtMs);
        $responseText = trim((string) $response);

        if ($responseText === '') {
            throw new RuntimeException('AI chatbot returned an empty response.');
        }

        if ($history === []) {
            Cache::put($cacheKey, $responseText, now()->addDays(3));
        }
        $this->complete($chatLog, $responseText, $enqueuedAtMs, $providerDurationMs);

        return $responseText;
    }

    public static function cacheKey(
        string $topicContext,
        ?string $chatbotPrompt,
        string $prompt,
    ): string {
        return 'ai_chat_v2:'.md5(
            $topicContext.':'.($chatbotPrompt ?? '').':'.trim(mb_strtolower($prompt)),
        );
    }

    public static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function complete(
        AiChatLog $chatLog,
        string $response,
        int $enqueuedAtMs,
        int $providerDurationMs,
    ): void {
        $totalDurationMs = max(0, self::nowMilliseconds() - $enqueuedAtMs);

        $chatLog->forceFill([
            'response' => $response,
            'status' => AiChatLog::STATUS_COMPLETED,
            'completed_at' => now(),
            'error_code' => null,
            'provider_duration_ms' => $providerDurationMs,
            'total_duration_ms' => $totalDurationMs,
        ])->save();

        Log::info('AI chatbot request completed.', [
            'chat_log_id' => $chatLog->id,
            'processing_mode' => $chatLog->processing_mode,
            'queue_wait_ms' => $chatLog->queue_wait_ms,
            'direct_attempt_ms' => $chatLog->direct_attempt_ms,
            'provider_duration_ms' => $providerDurationMs,
            'total_duration_ms' => $totalDurationMs,
        ]);
    }

    private function enqueuedAtMilliseconds(AiChatLog $chatLog): int
    {
        if ($chatLog->enqueued_at_ms) {
            return (int) $chatLog->enqueued_at_ms;
        }

        return ((int) ($chatLog->created_at?->getTimestamp() ?? time())) * 1000;
    }

    /**
     * Build a bounded, chronological context from completed turns in this session.
     *
     * @return array<int, UserMessage|AssistantMessage>
     */
    private function conversationHistory(AiChatLog $chatLog): array
    {
        if (! $chatLog->session_id) {
            return [];
        }

        $turnLimit = max(1, min(30, (int) config('ai.chatbot.context_turns', 10)));
        $characterLimit = max(4_000, (int) config('ai.chatbot.context_characters', 24_000));
        $characters = 0;
        $selectedTurns = [];

        $turns = AiChatLog::query()
            ->where('session_id', $chatLog->session_id)
            ->where('id', '<', $chatLog->id)
            ->where('status', AiChatLog::STATUS_COMPLETED)
            ->whereNotNull('response')
            ->orderByDesc('id')
            ->limit($turnLimit)
            ->get(['prompt', 'response']);

        foreach ($turns as $turn) {
            $turnCharacters = mb_strlen($turn->prompt) + mb_strlen((string) $turn->response);

            if ($selectedTurns !== [] && $characters + $turnCharacters > $characterLimit) {
                break;
            }

            $selectedTurns[] = $turn;
            $characters += $turnCharacters;
        }

        $messages = [];

        foreach (array_reverse($selectedTurns) as $turn) {
            $messages[] = new UserMessage($turn->prompt);
            $messages[] = new AssistantMessage((string) $turn->response);
        }

        return $messages;
    }
}
