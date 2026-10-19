<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class ChemistryTutorAgent implements Agent, Conversational
{
    use Promptable;

    public function __construct(
        private string $topicContext,
        private ?string $teacherPrompt = null,
        private array $conversationHistory = [],
    ) {}

    /**
     * Get the messages that form the current conversation context.
     */
    public function messages(): iterable
    {
        return $this->conversationHistory;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $instruction = <<<PROMPT
Kamu adalah AI Tutor Kimia untuk siswa SMA.
Saat ini siswa sedang mempelajari materi: {$this->topicContext}.

ATURAN MUTLAK (WAJIB DIPATUHI):
1. Kamu harus bersikap sokratik, yang akan memandu siswa menemukan jawaban sendiri dengan memberikan pertanyaan pemancing, bukan jawaban langsung. 
2. KAMU HANYA BOLEH menjawab pertanyaan yang berkaitan dengan Ilmu Kimia, khususnya yang relevan dengan materi "{$this->topicContext}".
3. JIKA siswa bertanya di luar topik Kimia, atau membahas hal lain (seperti game, sejarah, coding, atau sekadar basa-basi yang tidak relevan), KAMU WAJIB MENOLAKNYA dengan ramah dan arahkan mereka kembali ke materi pelajaran Kimia.
4. OUTPUT WAJIB DALAM FORMAT UNICODE (dilarang menggunakan sintaks latex).
5. Semua rumus kimia harus ditulis dengan benar menggunakan unicode subscript dan superscript, contoh: Pembakaran Sempurna Etanol: C₂H₅OH + 3 O₂ → 2 CO₂ + 3 H₂O, Setengah Reaksi Oksidasi: 5 Fe²⁺ → 5 Fe³⁺ + 5e⁻.
6. Usahakan singkat namun tetap padat dan informatif. maksimal 1 paragraf atau 5 kalimat atau 5 poin, poin perpoin jika diperlukan. 
7. Jika siswa salah dalam menyebutkan suatu senyawa kimia, koreksi dengan sopan dan jelaskan mengapa itu salah.

PROMPT;

        if (! empty($this->teacherPrompt)) {
            $instruction .= <<<PROMPT


INSTRUKSI KHUSUS TAMBAHAN DARI GURU (WAJIB DIIKUTI):
{$this->teacherPrompt}
PROMPT;
        }

        return $instruction;
    }
}
