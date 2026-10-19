<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class StudentAnswerEvaluatorAgent implements Agent
{
    use Promptable;

    private const DEFAULT_INSTRUCTIONS = '
        Kamu adalah guru Kimia yang menilai jawaban siswa. 
        Berikan umpan balik atas jawaban siswa ini secara singkat, lugas dan jelas. 
        DILARANG MEMBERIKAN JAWABAN DARI SOAL. TAPI BERIKAN PENJELASAN SINGKAT APA YANG KURANG/SALAH.
        OUTPUT WAJIB DALAM FORMAT UNICODE (dilarang menggunakan sintaks LaTeX), 
        semua rumus kimia harus ditulis dengan benar menggunakan unicode subscript dan superscript, 
        contoh: Pembakaran Sempurna Etanol: C₂H₅OH + 3 O₂ → 2 CO₂ + 3 H₂O, Setengah Reaksi Oksidasi: 5 Fe²⁺ → 5 Fe³⁺ + 5e⁻.
        umpan balik maksimal 1 paragraf, atau 5 kalimat, atau 5 poin (poin perpoin jika diperlukan), usahakan singkat namun tetap padat dan informatif. 
    ';

    public function __construct(
        private ?string $teacherPrompt = null,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return $this->teacherPrompt ?? self::DEFAULT_INSTRUCTIONS;
    }
}
