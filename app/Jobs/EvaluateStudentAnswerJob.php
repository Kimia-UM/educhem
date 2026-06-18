<?php

namespace App\Jobs;

use App\Models\StudentAnswer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use function Laravel\Ai\agent;

class EvaluateStudentAnswerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $answer;
    public $systemPrompt;

    public $timeout = 120;
    public $tries = 50; // Kita naikkan jadi 50 kali percobaan untuk menghadapi rate limit Gemini

    public function __construct(StudentAnswer $answer, ?string $systemPrompt)
    {
        $this->answer = $answer;
        $this->systemPrompt = $systemPrompt;
    }

    public function handle(): void
    {
        $this->answer->load('content');
        
        $question = $this->answer->content->content_data['question'] ?? 'Pertanyaan tidak diketahui';
        $studentAnswerText = $this->answer->answer_data;

        if (empty($studentAnswerText)) return;

        $defaultPrompt = "Kamu adalah guru Kimia yang menilai jawaban siswa. Berikan umpan balik atas jawaban siswa ini.";
        $instruction = $this->systemPrompt ?: $defaultPrompt;

        $userMessage = "PERTANYAAN:\n{$question}\n\nJAWABAN SISWA:\n{$studentAnswerText}\n\nBerikan evaluasi atau feedbackmu:";

        try {
            $response = agent(
                instructions: $instruction
            )->prompt($userMessage);

            if ($response) {
                $this->answer->update([
                    'ai_feedback' => (string) $response
                ]);
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Trik Pro: Jika error karena limit dari Google (429 atau 503)
            if (str_contains($errorMessage, '429') || str_contains($errorMessage, '503') || str_contains($errorMessage, 'overloaded') || str_contains($errorMessage, 'quota')) {
                Log::warning("Gemini Limit API tercapai. Menunda antrean selama 60 detik...");
                
                // Lempar kembali ke antrean, suruh tunggu 60 detik baru dieksekusi lagi
                $this->release(60); 
                return;
            }

            // Jika error lain (misal kodingan salah), baru lempar sebagai error beneran
            Log::error('Laravel AI SDK Exception: ' . $errorMessage);
            throw $e;
        }
    }
}