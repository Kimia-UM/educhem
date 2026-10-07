<?php

namespace Tests\Feature\Guru;

use App\Models\Classroom;
use App\Models\PhaseContent;
use App\Models\StudentAnswer;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAnswerEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $student;

    private Classroom $classroom;

    private Topic $topic;

    private TopicPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('GURU', 'web');
        Role::findOrCreate('SISWA', 'web');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('GURU');
        $this->student = User::factory()->create();
        $this->student->assignRole('SISWA');

        $this->classroom = Classroom::create([
            'teacher_id' => $this->teacher->id,
            'class_name' => 'Kelas Uji',
            'class_code' => 'GRADE1',
            'description' => 'Kelas untuk pengujian penilaian.',
        ]);
        $this->classroom->students()->attach($this->student->id);

        $this->topic = Topic::create([
            'title' => 'Stoikiometri',
            'description' => 'Materi uji.',
            'is_published' => true,
        ]);
        $this->classroom->topics()->attach($this->topic->id, ['is_open' => true]);

        $this->phase = TopicPhase::create([
            'topic_id' => $this->topic->id,
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

    public function test_teacher_can_reopen_one_submitted_phase(): void
    {
        $content = $this->createContent($this->phase, 'Jawaban fase utama');
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban lama',
            'evaluation' => 'benar',
            'is_locked' => true,
        ]);

        $otherPhase = TopicPhase::create([
            'topic_id' => $this->topic->id,
            'name' => 'Elaborate',
            'order' => 2,
        ]);
        $otherContent = $this->createContent($otherPhase, 'Jawaban fase lain');
        $otherAnswer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $otherPhase->id,
            'content_id' => $otherContent->id,
            'answer_data' => 'Tetap terkunci',
            'is_locked' => true,
        ]);

        $this->classroom->students()->updateExistingPivot($this->student->id, [
            'is_evaluation_finished' => true,
            'is_evaluation_sent' => true,
        ]);

        $response = $this->actingAs($this->teacher)->post(
            route('guru.classes.students.phases.reopen-submission', [
                'classroom' => $this->classroom,
                'student' => $this->student,
                'phase' => $this->phase,
            ]),
        );

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertFalse($answer->fresh()->is_locked);
        $this->assertSame('benar', $answer->fresh()->evaluation);
        $this->assertTrue($otherAnswer->fresh()->is_locked);

        $membership = DB::table('class_members')
            ->where('class_id', $this->classroom->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertFalse((bool) $membership->is_evaluation_finished);
        $this->assertFalse((bool) $membership->is_evaluation_sent);
    }

    public function test_another_teacher_cannot_reopen_the_submission(): void
    {
        $content = $this->createContent($this->phase, 'Jawaban terkunci');
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban lama',
            'is_locked' => true,
        ]);

        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('GURU');

        $this->actingAs($otherTeacher)->post(
            route('guru.classes.students.phases.reopen-submission', [
                'classroom' => $this->classroom,
                'student' => $this->student,
                'phase' => $this->phase,
            ]),
        )->assertForbidden();

        $this->assertTrue($answer->fresh()->is_locked);
    }

    public function test_teacher_cannot_reopen_a_phase_outside_the_class(): void
    {
        $foreignTopic = Topic::create([
            'title' => 'Topik Kelas Lain',
            'description' => 'Materi luar kelas.',
            'is_published' => true,
        ]);
        $foreignPhase = TopicPhase::create([
            'topic_id' => $foreignTopic->id,
            'name' => 'Engage',
            'order' => 1,
        ]);

        $this->actingAs($this->teacher)->post(
            route('guru.classes.students.phases.reopen-submission', [
                'classroom' => $this->classroom,
                'student' => $this->student,
                'phase' => $foreignPhase,
            ]),
        )->assertNotFound();
    }

    public function test_student_can_edit_and_submit_again_after_teacher_reopens_the_phase(): void
    {
        $content = $this->createContent($this->phase, 'Jawaban yang boleh diperbaiki');
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban lama',
            'evaluation' => 'salah',
            'is_locked' => true,
        ]);

        $this->actingAs($this->teacher)->post(
            route('guru.classes.students.phases.reopen-submission', [
                'classroom' => $this->classroom,
                'student' => $this->student,
                'phase' => $this->phase,
            ]),
        )->assertRedirect();

        $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $content->id,
                'answer_text' => 'Jawaban baru',
            ],
        )->assertOk();

        $answer->refresh();
        $this->assertSame('Jawaban baru', $answer->answer_data);
        $this->assertNull($answer->evaluation);
        $this->assertFalse($answer->is_locked);

        $this->actingAs($this->student)->post(
            route('siswa.phases.complete', [
                'classroom' => $this->classroom,
                'phase' => $this->phase,
            ]),
        )->assertRedirect(route('siswa.classes.show', $this->classroom->id));

        $this->assertTrue($answer->fresh()->is_locked);
    }

    private function createContent(TopicPhase $phase, string $question): PhaseContent
    {
        return PhaseContent::create([
            'topic_phase_id' => $phase->id,
            'type' => 'eval_essay',
            'content_data' => ['question' => $question],
            'order' => 1,
        ]);
    }
}
