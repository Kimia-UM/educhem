<?php

namespace App\Jobs;

use App\Models\AiChatLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

class ProcessAiChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $chatLog;
    public $topicContext;
    public $chatbotPrompt;

    public $timeout = 120;
    public $tries = 50; // Kita naikkan jadi 50 kali percobaan untuk menghadapi rate limit Gemini

    public function __construct(AiChatLog $chatLog, string $topicContext = '', string $chatbotPrompt = null)
    {
        $this->chatLog = $chatLog;
        $this->topicContext = $topicContext;
        $this->chatbotPrompt = $chatbotPrompt;
    }

    public function handle(): void
    {
        // PENGAMANAN KETAT (Prompt Guardrailing - Terbatas Pada Materi Kimia)
        $systemInstruction = "Kamu adalah AI Tutor Kimia untuk siswa SMA. 
        Saat ini siswa sedang mempelajari materi: {$this->topicContext}.
        
        ATURAN MUTLAK (WAJIB DIPATUHI):
        1. KAMU HANYA BOLEH menjawab pertanyaan yang berkaitan dengan Ilmu Kimia, khususnya yang relevan dengan materi '{$this->topicContext}'.
        2. JIKA siswa bertanya di luar topik Kimia, atau membahas hal lain (seperti game, sejarah, coding, atau sekadar basa-basi yang tidak relevan), KAMU WAJIB MENOLAKNYA dengan ramah dan arahkan mereka kembali ke materi pelajaran Kimia.";

        // Tambahkan instruksi khusus guru untuk Chatbot jika ada
        if (!empty($this->chatbotPrompt)) {
            $systemInstruction .= "\n\nINSTRUKSI KHUSUS TAMBAHAN DARI GURU (WAJIB DIIKUTI):\n{$this->chatbotPrompt}";
        }

        try {
            $response = agent(
                instructions: $systemInstruction
            )->prompt($this->chatLog->prompt);

            if ($response) {
                $this->chatLog->update([
                    'response' => (string) $response
                ]);
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, '429') || str_contains($errorMessage, '503') || str_contains($errorMessage, 'overloaded') || str_contains($errorMessage, 'quota')) {
                Log::warning("Gemini Limit API saat Chatbot berjalan. Menunda antrean 30 detik...");
                $this->release(30); 
                return;
            }

            Log::error('Chatbot AI Exception: ' . $errorMessage);
            throw $e;
        }
    }
}