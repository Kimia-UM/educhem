<?php

namespace App\Http\Controllers\Siswa;

use App\Ai\Agents\ChemistryTutorAgent;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiChatJob;
use App\Models\AiChatLog;
use App\Models\TopicPhase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    // Mengambil riwayat chat
    public function index()
    {
        try {
            // Pastikan user sudah login
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $logs = AiChatLog::where('user_id', auth()->id())
                ->orderBy('created_at', 'asc')
                ->get();
                
            return response()->json($logs);

        } catch (\Exception $e) {
            Log::error('Chatbot Index Error: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    // Menyimpan pertanyaan, mengecek Cache, lalu menghasilkan jawaban secara langsung
    public function store(Request $request)
    {
        try {
            // 1. Validasi Input
            $request->validate([
                'prompt' => 'required|string',
                'topic_context' => 'nullable|string',
                'phase_id' => 'nullable|integer|exists:topic_phases,id'
            ]);

            // 2. Pastikan user valid dan terautentikasi
            if (!auth()->check()) {
                return response()->json(['error' => 'Sesi Anda telah habis. Silakan refresh halaman.'], 401);
            }

            $topicContext = $request->topic_context ?? 'Materi Kimia';
            $chatbotPrompt = null;

            // Ambil prompt instruksi khusus dari fase jika ada
            if ($request->filled('phase_id')) {
                $phase = TopicPhase::find($request->phase_id);
                if ($phase) {
                    $chatbotPrompt = $phase->chatbot_prompt_setting;
                }
            }

            // 3. Cek apakah respon untuk pertanyaan & topik ini sudah ada di Cache
            $cacheKey = 'ai_chat_v1:' . md5($topicContext . ':' . ($chatbotPrompt ?? '') . ':' . trim(mb_strtolower($request->prompt)));
            $cachedResponse = Cache::get($cacheKey);

            if ($cachedResponse) {
                $chatLog = AiChatLog::create([
                    'user_id' => auth()->id(),
                    'prompt' => $request->prompt,
                    'response' => (string) $cachedResponse,
                ]);

                return response()->json([
                    'status' => 'success',
                    'response' => (string) $cachedResponse,
                    'log_id' => $chatLog->id,
                    'cached' => true,
                ]);
            }

            // 4. Jika belum ada di Cache, jalankan Direct Response ke AI Agent
            try {
                $agent = new ChemistryTutorAgent($topicContext, $chatbotPrompt);
                $aiResponse = (string) $agent->prompt($request->prompt);

                if (!empty($aiResponse)) {
                    // Simpan ke Cache selama 3 hari
                    Cache::put($cacheKey, $aiResponse, now()->addDays(3));

                    $chatLog = AiChatLog::create([
                        'user_id' => auth()->id(),
                        'prompt' => $request->prompt,
                        'response' => $aiResponse,
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'response' => $aiResponse,
                        'log_id' => $chatLog->id,
                        'cached' => false,
                    ]);
                }
            } catch (\Exception $aiException) {
                Log::warning('Direct AI Chat failed, falling back to background queue: ' . $aiException->getMessage());
            }

            // 5. Fallback ke Background Queue jika Direct Response mengalami kendala sementara
            $chatLog = AiChatLog::create([
                'user_id' => auth()->id(),
                'prompt' => $request->prompt,
                'response' => null,
            ]);

            ProcessAiChatJob::dispatch(
                $chatLog, 
                $topicContext,
                $chatbotPrompt
            );

            return response()->json([
                'status' => 'queued', 
                'log_id' => $chatLog->id
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot Store Error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}