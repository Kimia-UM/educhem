<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class StudentAnswerEvaluatorAgent implements Agent
{
    use Promptable;

    private const DEFAULT_INSTRUCTIONS = 'Kamu adalah guru Kimia yang menilai jawaban siswa. Berikan umpan balik atas jawaban siswa ini. OUTPUT WAJIB DALAM FORMAT UNICODE (dilarang menggunakan sintaks latex).
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