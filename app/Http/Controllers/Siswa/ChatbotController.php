<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiChatJob;
use App\Models\AiChatLog;
use App\Models\TopicPhase;
use App\Services\AiChatDirectSlotPool;
use App\Services\AiChatProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ChatbotController extends Controller
{
    // Mengambil riwayat chat
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'classroom_id' => 'required|integer|exists:classes,id',
            ]);
            $classroomId = (int) $validated['classroom_id'];

            $this->ensureClassMembership($classroomId, (int) $request->user()->id);

            $logs = AiChatLog::where('user_id', auth()->id())
                ->where('classroom_id', $classroomId)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->sortBy('id')
                ->values();

            return response()->json($logs);

        } catch (ValidationException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Chatbot Index Error: '.$e->getMessage());

            return response()->json(['error' => 'Gagal mengambil riwayat AI Tutor.'], 500);
        }
    }

    // Cache hit dijawab langsung. Cache miss memakai empat slot direct call dan
    // otomatis jatuh kembali ke queue ketika seluruh slot sibuk atau provider gagal.
    public function store(
        Request $request,
        AiChatDirectSlotPool $slotPool,
        AiChatProcessor $processor,
    ) {
        $requestStartedAtMs = AiChatProcessor::nowMilliseconds();

        try {
            // 1. Validasi Input
            $request->validate([
                'prompt' => 'required|string',
                'topic_context' => 'nullable|string',
                'phase_id' => 'nullable|integer|exists:topic_phases,id',
                'classroom_id' => 'required|integer|exists:classes,id',
            ]);

            // 2. Pastikan user valid dan terautentikasi
            if (! auth()->check()) {
                return response()->json(['error' => 'Sesi Anda telah habis. Silakan refresh halaman.'], 401);
            }

            $topicContext = $request->topic_context ?? 'Materi Kimia';
            $chatbotPrompt = null;
            $classroomId = $request->integer('classroom_id');

            $this->ensureClassMembership($classroomId, (int) auth()->id());

            // Ambil prompt instruksi khusus dari fase jika ada
            if ($request->filled('phase_id')) {
                $phase = TopicPhase::with('topic')->find($request->phase_id);
                if ($phase) {
                    if (! $phase->is_chatbot_enabled || ! $phase->topic?->is_published) {
                        abort(403, 'AI Tutor tidak tersedia untuk fase ini.');
                    }

                    $hasAccess = DB::table('class_members')
                        ->join('class_topic_accesses', 'class_topic_accesses.class_id', '=', 'class_members.class_id')
                        ->where('class_members.user_id', auth()->id())
                        ->where('class_members.class_id', $classroomId)
                        ->where('class_topic_accesses.class_id', $classroomId)
                        ->where('class_topic_accesses.topic_id', $phase->topic_id)
                        ->exists();

                    if (! $hasAccess) {
                        abort(403, 'Akses ditolak. Anda tidak memiliki akses ke fase ini.');
                    }

                    $chatbotPrompt = $phase->chatbot_prompt_setting;
                }
            }

            // 3. Cek apakah respon untuk pertanyaan & topik ini sudah ada di Cache
            $cacheKey = AiChatProcessor::cacheKey(
                $topicContext,
                $chatbotPrompt,
                (string) $request->prompt,
            );
            $cachedResponse = Cache::get($cacheKey);

            if (is_string($cachedResponse) && $cachedResponse !== '') {
                $totalDurationMs = max(
                    0,
                    AiChatProcessor::nowMilliseconds() - $requestStartedAtMs,
                );
                $chatLog = AiChatLog::create([
                    'user_id' => auth()->id(),
                    'classroom_id' => $classroomId,
                    'prompt' => $request->prompt,
                    'response' => (string) $cachedResponse,
                    'status' => AiChatLog::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'processing_mode' => 'cache',
                    'enqueued_at_ms' => $requestStartedAtMs,
                    'processing_started_at' => now(),
                    'queue_wait_ms' => 0,
                    'provider_duration_ms' => 0,
                    'total_duration_ms' => $totalDurationMs,
                ]);

                return response()->json([
                    'status' => 'success',
                    'response' => (string) $cachedResponse,
                    'log_id' => $chatLog->id,
                    'cached' => true,
                ]);
            }

            $enqueuedAtMs = AiChatProcessor::nowMilliseconds();
            $chatLog = AiChatLog::create([
                'user_id' => auth()->id(),
                'classroom_id' => $classroomId,
                'prompt' => $request->prompt,
                'response' => null,
                'status' => AiChatLog::STATUS_QUEUED,
                'enqueued_at_ms' => $enqueuedAtMs,
            ]);

            $slot = $slotPool->acquire();

            if ($slot === null) {
                return $this->dispatchToQueue(
                    $chatLog,
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
                    'direct_attempt_ms' => $directAttemptMs,
                    'error_code' => $chatLog->error_code,
                    'exception' => $exception::class,
                ]);

                return $this->dispatchToQueue(
                    $chatLog,
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

        } catch (ValidationException|HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Chatbot Store Error: '.$e->getMessage());

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

    private function ensureClassMembership(int $classroomId, int $userId): void
    {
        $isMember = DB::table('class_members')
            ->where('class_id', $classroomId)
            ->where('user_id', $userId)
            ->exists();

        abort_unless($isMember, 403, 'Akses ditolak. Anda bukan anggota kelas ini.');
    }
}
