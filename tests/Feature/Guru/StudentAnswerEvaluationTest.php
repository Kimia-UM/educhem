<?php

namespace Tests\Feature\Guru;

use App\Models\Classroom;
use App\Models\PhaseContent;
use App\Models\StudentAnswer;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAnswerEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private TopicPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('GURU', 'web');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('GURU');
        $this->student = User::factory()->create();

        $classroom = Classroom::create([
            'teacher_id' => $this->teacher->id,
            'class_name' => 'Kelas Uji',
            'class_code' => 'GRADE1',
            'description' => 'Kelas untuk pengujian penilaian.',
        ]);
        $classroom->students()->attach($this->student->id);

        $topic = Topic::create([
            'title' => 'Stoikiometri',
            'description' => 'Materi uji.',
            'is_published' => true,
        ]);
        $classroom->topics()->attach($topic->id, ['is_open' => true]);

        $this->phase = TopicPhase::create([
            'topic_id' => $topic->id,
            'name' => 'Evaluate',
            'order' => 1,
        ]);
    }

    public function test_excluded_auto_graded_answers_can_be_included_again(): void
    {
        foreach (['eval_mcq', 'eval_cmcq'] as $type) {
            $content = PhaseContent::create([
                'topic_phase_id' => $this->phase->id,
                'type' => $type,
                'content_data' => [
                    'question' => "Pertanyaan {$type}",
                    'options' => ['Pilihan A', 'Pilihan B'],
                ],
                'correct_answers' => [0],
                'order' => 1,
            ]);
            $answer = StudentAnswer::create([
                'user_id' => $this->student->id,
                'phase_id' => $this->phase->id,
                'content_id' => $content->id,
                'answer_data' => $type === 'eval_mcq' ? 'Pilihan A' : json_encode(['Pilihan A']),
                'evaluation' => 'tidak_dinilai',
            ]);

            $response = $this->actingAs($this->teacher)->post(
                route('guru.answers.evaluate', $answer),
                ['evaluation' => null],
            );

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();
            $this->assertNull($answer->fresh()->evaluation);
        }
    }

    public function test_evaluation_field_must_still_be_present(): void
    {
        $content = PhaseContent::create([
            'topic_phase_id' => $this->phase->id,
            'type' => 'eval_mcq',
            'content_data' => ['question' => 'Pertanyaan', 'options' => ['A', 'B']],
            'correct_answers' => [0],
            'order' => 1,
        ]);
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'A',
            'evaluation' => 'tidak_dinilai',
        ]);

        $response = $this->actingAs($this->teacher)->post(
            route('guru.answers.evaluate', $answer),
            [],
        );

        $response->assertSessionHasErrors('evaluation');
        $this->assertSame('tidak_dinilai', $answer->fresh()->evaluation);
    }
}
