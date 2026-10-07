<?php

namespace App\Jobs;

use App\Models\AiChatLog;
use App\Services\AiChatProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    public function __construct(
        public AiChatLog $chatLog,
        public string $topicContext = '',
        public ?string $chatbotPrompt = null,
    ) {
        $this->onQueue('ai-chat');
    }

    public function handle(AiChatProcessor $processor): void
    {
        $this->chatLog->refresh();

        if ($this->chatLog->status === AiChatLog::STATUS_COMPLETED) {
            return;
        }

        $mode = $this->chatLog->direct_attempt_ms === null
            ? 'queue'
            : 'queue_fallback';

        try {
            $processor->process(
                $this->chatLog,
                $this->topicContext,
                $this->chatbotPrompt,
                $mode,
                max(1, (int) config('ai.chatbot.queue_timeout', 60)),
            );
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, '429') || str_contains($errorMessage, '503') || str_contains($errorMessage, 'overloaded') || str_contains($errorMessage, 'quota')) {
                if ($this->attempts() < $this->tries) {
                    $delay = $this->attempts() === 1 ? 15 : 30;
                    $this->chatLog->update([
                        'status' => AiChatLog::STATUS_QUEUED,
                        'error_code' => 'provider_busy',
                    ]);
                    Log::warning("Gemini sedang sibuk saat Chatbot berjalan. Menunda antrean {$delay} detik.");
                    $this->release($delay);

                    return;
                }
            }

            Log::error('ChemistryTutorAgent Exception: '.$errorMessage);
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $enqueuedAtMs = $this->chatLog->enqueued_at_ms
            ?: ((int) ($this->chatLog->created_at?->getTimestamp() ?? time())) * 1000;

        $this->chatLog->update([
            'status' => AiChatLog::STATUS_FAILED,
            'error_code' => 'chat_failed',
            'total_duration_ms' => max(
                0,
                AiChatProcessor::nowMilliseconds() - (int) $enqueuedAtMs,
            ),
        ]);

        Log::error('Chatbot job gagal permanen.', [
            'chat_log_id' => $this->chatLog->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
