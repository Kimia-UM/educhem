<?php

namespace App\Jobs;

use App\Ai\Agents\StudentAnswerEvaluatorAgent;
use App\Models\StudentAnswer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class EvaluateStudentAnswerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    /**
     * Kept as a non-promoted property so jobs serialized before this field
     * existed can still be restored safely after deployment.
     */
    public ?int $expectedAnswerVersion = null;

    public function __construct(
        public StudentAnswer $answer,
        public ?string $systemPrompt = null,
        ?int $expectedAnswerVersion = null,
    ) {
        $this->expectedAnswerVersion = $expectedAnswerVersion;
        $this->onQueue('ai-evaluation');
    }

    public function handle(): void
    {
        $this->answer->refresh()->load('content');

        // Legacy queued jobs do not carry a version. Pin them to the version
        // loaded at the start so a later student edit cannot receive stale AI.
        $answerVersion = $this->expectedAnswerVersion
            ?? max(1, (int) ($this->answer->answer_version ?? 1));

        if (! $this->isCurrentVersion($answerVersion)) {
            return;
        }

        $this->updateCurrentVersion($answerVersion, [
            'ai_status' => StudentAnswer::AI_STATUS_PROCESSING,
            'ai_error_code' => null,
        ]);

        $question = $this->answer->content->content_data['question'] ?? 'Pertanyaan tidak diketahui';
        $studentAnswerText = $this->answer->answer_data;

        if (empty($studentAnswerText)) {
            $this->updateCurrentVersion($answerVersion, [
                'ai_status' => StudentAnswer::AI_STATUS_FAILED,
                'ai_error_code' => 'empty_answer',
            ]);

            return;
        }

        // Cek Cache untuk jawaban identik pada pertanyaan ini
        $cacheKey = 'ai_eval_v1:'.md5(($this->answer->content_id ?? '0').':'.($this->systemPrompt ?? '').':'.trim(mb_strtolower($studentAnswerText)));
        $cachedFeedback = Cache::get($cacheKey);

        if ($cachedFeedback) {
            $this->updateCurrentVersion($answerVersion, [
                'ai_feedback' => (string) $cachedFeedback,
                'ai_status' => StudentAnswer::AI_STATUS_COMPLETED,
                'ai_completed_at' => now(),
                'ai_error_code' => null,
            ]);

            return;
        }

        $userMessage = <<<MSG
PERTANYAAN:
{$question}

JAWABAN SISWA:
{$studentAnswerText}

Berikan evaluasi atau feedbackmu:
MSG;

        try {
            $response = (new StudentAnswerEvaluatorAgent($this->systemPrompt))
                ->prompt($userMessage);

            if ($response) {
                $feedbackStr = (string) $response;
                Cache::put($cacheKey, $feedbackStr, now()->addDays(7));

                $this->updateCurrentVersion($answerVersion, [
                    'ai_feedback' => $feedbackStr,
                    'ai_status' => StudentAnswer::AI_STATUS_COMPLETED,
                    'ai_completed_at' => now(),
                    'ai_error_code' => null,
                ]);

                return;
            }

            throw new \RuntimeException('AI evaluator returned an empty response.');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, '429') || str_contains($errorMessage, '503') || str_contains($errorMessage, 'overloaded') || str_contains($errorMessage, 'quota')) {
                if ($this->attempts() < $this->tries) {
                    $delay = match ($this->attempts()) {
                        1 => 15,
                        2 => 30,
                        default => 60,
                    };

                    $this->updateCurrentVersion($answerVersion, [
                        'ai_status' => StudentAnswer::AI_STATUS_QUEUED,
                        'ai_error_code' => 'provider_busy',
                    ]);

                    Log::warning("Gemini sedang sibuk saat evaluasi jawaban. Menunda antrean {$delay} detik.");
                    $this->release($delay);

                    return;
                }
            }

            Log::error('StudentAnswerEvaluatorAgent Exception: '.$errorMessage);
            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->answer->refresh();
        $answerVersion = $this->expectedAnswerVersion
            ?? max(1, (int) ($this->answer->answer_version ?? 1));

        $this->updateCurrentVersion($answerVersion, [
            'ai_status' => StudentAnswer::AI_STATUS_FAILED,
            'ai_error_code' => 'evaluation_failed',
        ]);

        Log::error('Evaluasi jawaban siswa gagal permanen.', [
            'answer_id' => $this->answer->id,
            'answer_version' => $answerVersion,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function isCurrentVersion(int $answerVersion): bool
    {
        return StudentAnswer::query()
            ->whereKey($this->answer->id)
            ->where('answer_version', $answerVersion)
            ->exists();
    }

    private function updateCurrentVersion(int $answerVersion, array $attributes): int
    {
        return StudentAnswer::query()
            ->whereKey($this->answer->id)
            ->where('answer_version', $answerVersion)
            ->update($attributes);
    }
}
