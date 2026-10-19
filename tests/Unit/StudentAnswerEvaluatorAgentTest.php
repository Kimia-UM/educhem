<?php

namespace Tests\Unit;

use App\Ai\Agents\StudentAnswerEvaluatorAgent;
use Tests\TestCase;

class StudentAnswerEvaluatorAgentTest extends TestCase
{
    public function test_teacher_prompt_supplements_instead_of_replacing_master_instructions(): void
    {
        $teacherPrompt = 'Berikan petunjuk yang berkaitan dengan laju reaksi.';
        $instructions = (string) (new StudentAnswerEvaluatorAgent($teacherPrompt))->instructions();

        $this->assertStringContainsString(
            'OUTPUT WAJIB DALAM FORMAT UNICODE',
            $instructions,
        );
        $this->assertStringContainsString(
            'DILARANG MEMBERIKAN JAWABAN DARI SOAL',
            $instructions,
        );
        $this->assertStringContainsString(
            'INSTRUKSI KHUSUS TAMBAHAN DARI GURU',
            $instructions,
        );
        $this->assertStringContainsString($teacherPrompt, $instructions);
        $this->assertLessThan(
            strpos($instructions, $teacherPrompt),
            strpos($instructions, 'OUTPUT WAJIB DALAM FORMAT UNICODE'),
        );
    }
}
