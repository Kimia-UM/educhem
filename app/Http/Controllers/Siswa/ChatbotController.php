<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiChatJob;
use App\Models\AiChatLog;
use App\Models\AiChatSession;
use App\Models\TopicPhase;
use App\Services\AiChatDirectSlotPool;
use App\Services\AiChatProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ChatbotController extends Controller
{
    /**
     * Legacy classroom-wide history endpoint kept for backward compatibility.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'classroom_id' => 'required|integer|exists:classes,id',
            ]);
            $classroomId = (int) $validated['classroom_id'];

            $this->ensureClassMembership($classroomId, (int) $request->user()->id);

            $logs = AiChatLog::query()
                ->where('user_id', $request->user()->id)
                ->where('classroom_id', $classroomId)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->sortBy('id')
                ->values();

            return response()->json($logs);
        } catch (ValidationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Chatbot Index Error: '.$exception->getMessage());

            return response()->json(['error' => 'Gagal mengambil riwayat AI Tutor.'], 500);
        }
    }

    /**
     * List visible conversation sessions for one learning phase.
     */
    public function sessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|integer|exists:classes,id',
            'phase_id' => 'required|integer|exists:topic_phases,id',
        ]);
        $userId = (int) $request->user()->id;
        $classroomId = (int) $validated['classroom_id'];
        $phaseId = (int) $validated['phase_id'];

        $this->ensureClassMembership($classroomId, $userId);
        $this->resolveAccessiblePhase($phaseId, $classroomId, $userId);

        $sessions = AiChatSession::query()
            ->where('user_id', $userId)
            ->where('classroom_id', $classroomId)
            ->where('phase_id', $phaseId)
            ->whereNull('cleared_at')
            ->where('is_legacy', false)
            ->withExists([
                'logs as has_pending' => fn ($query) => $query->whereIn('status', [
                    AiChatLog::STATUS_QUEUED,
                    AiChatLog::STATUS_PROCESSING,
                ]),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'sessions' => $sessions
                ->map(fn (AiChatSession $session) => $this->serializeSession($session))
                ->values(),
        ]);
    }

    /**
     * Return the most recent messages for one authorized session.
     */
    public function history(Request $request, AiChatSession $chatSession): JsonResponse
    {
        $this->authorizeSession($chatSession, (int) $request->user()->id);
        $this->ensureClassMembership((int) $chatSession->classroom_id, (int) $request->user()->id);

        if ($chatSession->phase_id) {
            $this->resolveAccessiblePhase(
                (int) $chatSession->phase_id,
                (int) $chatSession->classroom_id,
                (int) $request->user()->id,
            );
        }

        $logs = $chatSession->logs()
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (AiChatLog $log) => [
                'id' => $log->id,
                'prompt' => $log->prompt,
                'response' => $log->response,
                'status' => $log->status,
                'error_code' => $log->error_code,
                'created_at' => $log->created_at?->toISOString(),
                'completed_at' => $log->completed_at?->toISOString(),
            ]);

        $chatSession->setAttribute(
            'has_pending',
            $chatSession->logs()
                ->whereIn('status', [
                    AiChatLog::STATUS_QUEUED,
                    AiChatLog::STATUS_PROCESSING,
                ])
                ->exists(),
        );

        return response()->json([
            'session' => $this->serializeSession($chatSession),
            'messages' => $logs,
        ]);
    }

    /**
     * Hide a conversation from the student without deleting teacher audit logs.
     */
    public function clear(Request $request, AiChatSession $chatSession): JsonResponse
    {
        $this->authorizeSession($chatSession, (int) $request->user()->id, allowCleared: true);
        $this->ensureClassMembership((int) $chatSession->classroom_id, (int) $request->user()->id);

        if (! $chatSession->cleared_at) {
            $chatSession->forceFill(['cleared_at' => now()])->save();
        }

        return response()->json(['status' => 'cleared']);
    }

    /**
     * Store a turn. A missing session_id starts a new conversation lazily.
     */
    public function store(
        Request $request,
        AiChatDirectSlotPool $slotPool,
        AiChatProcessor $processor,
    ): JsonResponse {
        $requestStartedAtMs = AiChatProcessor::nowMilliseconds();

        try {
            $validated = $request->validate([
                'prompt' => 'required|string|max:4000',
                'session_id' => 'nullable|uuid',
                'phase_id' => 'required|integer|exists:topic_phases,id',
                'classroom_id' => 'required|integer|exists:classes,id',
            ]);
            $userId = (int) $request->user()->id;
            $classroomId = (int) $validated['classroom_id'];
            $phaseId = (int) $validated['phase_id'];
            $prompt = trim($validated['prompt']);

            if ($prompt === '') {
                throw ValidationException::withMessages([
                    'prompt' => 'Pertanyaan tidak boleh kosong.',
                ]);
            }

            $this->ensureClassMembership($classroomId, $userId);
            $phase = $this->resolveAccessiblePhase($phaseId, $classroomId, $userId);
            $topicContext = $phase->topic?->title ?: 'Materi Kimia';
            $chatbotPrompt = $phase->chatbot_prompt_setting;
            $enqueuedAtMs = AiChatProcessor::nowMilliseconds();

            [$chatSession, $chatLog, $hasHistory] = DB::transaction(function () use (
                $validated,
                $userId,
                $classroomId,
                $phaseId,
                $prompt,
                $enqueuedAtMs,
            ) {
                if (! empty($validated['session_id'])) {
                    $chatSession = AiChatSession::query()
                        ->whereKey($validated['session_id'])
                        ->where('user_id', $userId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->authorizeSession($chatSession, $userId);

                    abort_unless(
                        (int) $chatSession->classroom_id === $classroomId
                        && (int) $chatSession->phase_id === $phaseId
                        && ! $chatSession->is_legacy,
                        404,
                    );

                    $hasPendingTurn = $chatSession->logs()
                        ->whereIn('status', [
                            AiChatLog::STATUS_QUEUED,
                            AiChatLog::STATUS_PROCESSING,
                        ])
                        ->exists();

                    abort_if(
                        $hasPendingTurn,
                        409,
                        'Tunggu jawaban sebelumnya selesai sebelum mengirim pertanyaan berikutnya.',
                    );
                } else {
                    $chatSession = AiChatSession::create([
                        'user_id' => $userId,
                        'classroom_id' => $classroomId,
                        'phase_id' => $phaseId,
                        'title' => $this->sessionTitle($prompt),
                        'last_message_at' => now(),
                    ]);
                }

                $hasHistory = $chatSession->logs()
                    ->where('status', AiChatLog::STATUS_COMPLETED)
                    ->whereNotNull('response')
                    ->exists();

                $chatLog = AiChatLog::create([
                    'user_id' => $userId,
                    'classroom_id' => $classroomId,
                    'session_id' => $chatSession->id,
                    'prompt' => $prompt,
                    'response' => null,
                    'status' => AiChatLog::STATUS_QUEUED,
                    'enqueued_at_ms' => $enqueuedAtMs,
                ]);

                $chatSession->forceFill(['last_message_at' => now()])->save();

                return [$chatSession, $chatLog, $hasHistory];
            });

            if (! $hasHistory) {
                $cacheKey = AiChatProcessor::cacheKey($topicContext, $chatbotPrompt, $prompt);
                $cachedResponse = Cache::get($cacheKey);

                if (is_string($cachedResponse) && $cachedResponse !== '') {
                    $totalDurationMs = max(
                        0,
                        AiChatProcessor::nowMilliseconds() - $requestStartedAtMs,
                    );

                    $chatLog->forceFill([
                        'response' => $cachedResponse,
                        'status' => AiChatLog::STATUS_COMPLETED,
                        'completed_at' => now(),
                        'processing_mode' => 'cache',
                        'processing_started_at' => now(),
                        'queue_wait_ms' => 0,
                        'provider_duration_ms' => 0,
                        'total_duration_ms' => $totalDurationMs,
                    ])->save();

                    return response()->json([
                        'status' => 'success',
                        'response' => $cachedResponse,
                        'log_id' => $chatLog->id,
                        'session_id' => $chatSession->id,
                        'session_title' => $chatSession->title,
                        'cached' => true,
                    ]);
                }
            }

            $slot = $slotPool->acquire();

            if ($slot === null) {
                return $this->dispatchToQueue(
                    $chatLog,
                    $chatSession,
                    $topicContext,
                    $chatbotPrompt,
                    false,
                );
            }

            $directStartedAtMs = AiChatProcessor::nowMilliseconds();

            try {
                $responseText = $processor->process(
                    $chatLog,
                    $topicContext,
                    $chatbotPrompt,
                    'direct',
                    max(1, (int) config('ai.chatbot.direct_timeout', 12)),
                );

                return response()->json([
                    'status' => 'success',
                    'response' => $responseText,
                    'log_id' => $chatLog->id,
                    'session_id' => $chatSession->id,
                    'session_title' => $chatSession->title,
                    'cached' => false,
                    'processing_mode' => 'direct',
                ]);
            } catch (Throwable $exception) {
                $directAttemptMs = max(
                    0,
                    AiChatProcessor::nowMilliseconds() - $directStartedAtMs,
                );

                $chatLog->forceFill([
                    'status' => AiChatLog::STATUS_QUEUED,
                    'error_code' => $this->directFailureCode($exception),
                    'processing_mode' => 'queue_fallback',
                    'processing_started_at' => null,
                    'direct_attempt_ms' => $directAttemptMs,
                    'provider_duration_ms' => null,
                    'total_duration_ms' => null,
                ])->save();

                Log::warning('AI chatbot direct call fell back to the queue.', [
                    'chat_log_id' => $chatLog->id,
                    'session_id' => $chatSession->id,
                    'direct_attempt_ms' => $directAttemptMs,
                    'error_code' => $chatLog->error_code,
                    'exception' => $exception::class,
                ]);

                return $this->dispatchToQueue(
                    $chatLog,
                    $chatSession,
                    $topicContext,
                    $chatbotPrompt,
                    true,
                );
            } finally {
                try {
                    $slot->release();
                } catch (Throwable $exception) {
                    Log::warning('Unable to release an AI chatbot direct slot.', [
                        'chat_log_id' => $chatLog->id,
                        'exception' => $exception::class,
                    ]);
                }
            }
        } catch (ValidationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Chatbot Store Error: '.$exception->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'AI Tutor sedang mengalami gangguan. Silakan coba kembali nanti.',
            ], 500);
        }
    }

    public function status(Request $request, AiChatLog $chatLog): JsonResponse
    {
        abort_unless((int) $chatLog->user_id === (int) $request->user()->id, 404);

        $status = $chatLog->status;

        if ($status === null && $chatLog->response) {
            $status = AiChatLog::STATUS_COMPLETED;
        }

        return response()->json([
            'log_id' => $chatLog->id,
            'session_id' => $chatLog->session_id,
            'status' => $status ?? AiChatLog::STATUS_QUEUED,
            'response' => $status === AiChatLog::STATUS_COMPLETED
                ? $chatLog->response
                : null,
            'error_code' => $status === AiChatLog::STATUS_FAILED
                ? $chatLog->error_code
                : null,
            'processing_mode' => $chatLog->processing_mode,
            'queue_wait_ms' => $chatLog->queue_wait_ms,
            'provider_duration_ms' => $chatLog->provider_duration_ms,
            'total_duration_ms' => $chatLog->total_duration_ms,
            'created_at' => $chatLog->created_at?->toISOString(),
            'completed_at' => $chatLog->completed_at?->toISOString(),
        ]);
    }

    private function dispatchToQueue(
        AiChatLog $chatLog,
        AiChatSession $chatSession,
        string $topicContext,
        ?string $chatbotPrompt,
        bool $fallback,
    ): JsonResponse {
        ProcessAiChatJob::dispatch(
            $chatLog,
            $topicContext,
            $chatbotPrompt,
        )->afterCommit();

        return response()->json([
            'status' => 'queued',
            'log_id' => $chatLog->id,
            'session_id' => $chatSession->id,
            'session_title' => $chatSession->title,
            'fallback' => $fallback,
        ], 202);
    }

    private function directFailureCode(Throwable $exception): string
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'timeout') || str_contains($message, 'timed out')
            ? 'direct_timeout'
            : 'direct_failed';
    }

    private function resolveAccessiblePhase(int $phaseId, int $classroomId, int $userId): TopicPhase
    {
        $phase = TopicPhase::with('topic')->findOrFail($phaseId);

        if (! $phase->is_chatbot_enabled || ! $phase->topic?->is_published) {
            abort(403, 'AI Tutor tidak tersedia untuk fase ini.');
        }

        $hasAccess = DB::table('class_members')
            ->join('class_topic_accesses', 'class_topic_accesses.class_id', '=', 'class_members.class_id')
            ->where('class_members.user_id', $userId)
            ->where('class_members.class_id', $classroomId)
            ->where('class_topic_accesses.class_id', $classroomId)
            ->where('class_topic_accesses.topic_id', $phase->topic_id)
            ->exists();

        abort_unless($hasAccess, 403, 'Akses ditolak. Anda tidak memiliki akses ke fase ini.');

        return $phase;
    }

    private function authorizeSession(
        AiChatSession $chatSession,
        int $userId,
        bool $allowCleared = false,
    ): void {
        abort_unless((int) $chatSession->user_id === $userId, 404);

        if (! $allowCleared) {
            abort_if($chatSession->cleared_at !== null, 404);
        }
    }

    private function ensureClassMembership(int $classroomId, int $userId): void
    {
        $isMember = DB::table('class_members')
            ->where('class_id', $classroomId)
            ->where('user_id', $userId)
            ->exists();

        abort_unless($isMember, 403, 'Akses ditolak. Anda bukan anggota kelas ini.');
    }

    private function sessionTitle(string $prompt): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($prompt)) ?: 'Chat baru';

        return Str::limit($normalized, 60, preserveWords: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSession(AiChatSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'is_legacy' => $session->is_legacy,
            'has_pending' => (bool) ($session->getAttribute('has_pending') ?? false),
            'last_message_at' => $session->last_message_at?->toISOString(),
            'created_at' => $session->created_at?->toISOString(),
        ];
    }
}
