<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AiChatLog;
use App\Jobs\ProcessAiChatJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Wajib ditambahkan untuk mencatat error

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

    // Menyimpan pertanyaan dan melempar ke Queue
    public function store(Request $request)
    {
        try {
            // 1. Validasi Input
            $request->validate([
                'prompt' => 'required|string',
                'topic_context' => 'nullable|string'
            ]);

            // 2. Pastikan user valid dan terautentikasi (Mencegah error 'user_id cannot be null')
            if (!auth()->check()) {
                return response()->json(['error' => 'Sesi Anda telah habis. Silakan refresh halaman.'], 401);
            }

            // 3. Simpan log pertanyaan siswa ke database
            $chatLog = AiChatLog::create([
                'user_id' => auth()->id(),
                'prompt' => $request->prompt,
                'response' => null, // Dikosongkan karena menunggu AI
            ]);

            // 4. Lempar ke Queue agar dieksekusi di belakang layar (Terminal)
            ProcessAiChatJob::dispatch($chatLog, $request->topic_context ?? 'Materi Kimia');

            // 5. Kembalikan response sukses
            return response()->json([
                'status' => 'success', 
                'log_id' => $chatLog->id
            ]);

        } catch (\Exception $e) {
            // JIKA GAGAL: Catat ke laravel.log dan kembalikan pesan error aslinya!
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