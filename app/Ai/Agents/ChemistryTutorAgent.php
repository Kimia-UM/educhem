<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class ChemistryTutorAgent implements Agent
{
    use Promptable;

    public function __construct(
        private string $topicContext,
        private ?string $teacherPrompt = null,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $instruction = <<<PROMPT
Kamu adalah AI Tutor Kimia untuk siswa SMA.
Saat ini siswa sedang mempelajari materi: {$this->topicContext}.

ATURAN MUTLAK (WAJIB DIPATUHI):
1. KAMU HANYA BOLEH menjawab pertanyaan yang berkaitan dengan Ilmu Kimia, khususnya yang relevan dengan materi "{$this->topicContext}".
2. JIKA siswa bertanya di luar topik Kimia, atau membahas hal lain (seperti game, sejarah, coding, atau sekadar basa-basi yang tidak relevan), KAMU WAJIB MENOLAKNYA dengan ramah dan arahkan mereka kembali ke materi pelajaran Kimia.
3. OUTPUT WAJIB DALAM FORMAT UNICODE (dilarang menggunakan sintaks latex)
PROMPT;

        if (!empty($this->teacherPrompt)) {
            $instruction .= <<<PROMPT


INSTRUKSI KHUSUS TAMBAHAN DARI GURU (WAJIB DIIKUTI):
{$this->teacherPrompt}
PROMPT;
        }

        return $instruction;
    }
}