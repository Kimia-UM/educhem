<?php

namespace Tests\Feature\Siswa;

use App\Jobs\EvaluateStudentAnswerJob;
use App\Models\Classroom;
use App\Models\PhaseContent;
use App\Models\StudentAnswer;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorksheetAnswerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Classroom $classroom;

    private Topic $topic;

    private TopicPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('SISWA', 'web');

        $teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->student->assignRole('SISWA');

        $this->classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'class_name' => 'Kelas Uji',
            'class_code' => 'ABC123',
            'description' => 'Kelas untuk pengujian worksheet.',
        ]);
        $this->classroom->students()->attach($this->student->id);

        $this->topic = Topic::create([
            'title' => 'Stoikiometri',
            'description' => 'Materi uji.',
            'is_published' => true,
        ]);
        $this->classroom->topics()->attach($this->topic->id, [
            'is_open' => true,
        ]);

        $this->phase = TopicPhase::create([
            'topic_id' => $this->topic->id,
            'name' => 'Evaluate',
            'description' => 'Fase uji.',
            'order' => 1,
            'is_ai_enabled' => true,
            'ai_prompt_setting' => 'Berikan feedback singkat.',
        ]);
    }

    public function test_mcq_is_saved_without_dispatching_ai_job(): void
    {
        Queue::fake();
        $content = $this->createContent('eval_mcq');

        $response = $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $content->id,
                'answer_text' => 'Pilihan A',
            ],
        );

        $response->assertOk()->assertJson([
            'saved' => true,
            'content_id' => $content->id,
            'ai_status' => StudentAnswer::AI_STATUS_NOT_REQUIRED,
            'answer_version' => 1,
        ]);
        Queue::assertNotPushed(EvaluateStudentAnswerJob::class);
        $this->assertDatabaseHas('student_answers', [
            'user_id' => $this->student->id,
            'content_id' => $content->id,
            'answer_data' => 'Pilihan A',
            'ai_status' => StudentAnswer::AI_STATUS_NOT_REQUIRED,
        ]);
    }

    public function test_essay_is_queued_with_answer_version(): void
    {
        Queue::fake();
        $content = $this->createContent('eval_essay');

        $response = $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $content->id,
                'answer_text' => 'Jawaban esai siswa.',
            ],
        );

        $response->assertStatus(202)->assertJson([
            'saved' => true,
            'ai_status' => StudentAnswer::AI_STATUS_QUEUED,
            'answer_version' => 1,
        ]);
        Queue::assertPushed(
            EvaluateStudentAnswerJob::class,
            fn (EvaluateStudentAnswerJob $job) => $job->answer->content_id === $content->id
                && $job->expectedAnswerVersion === 1
                && $job->queue === 'ai-evaluation',
        );
    }

    public function test_resubmitting_answer_increments_version_and_resets_feedback(): void
    {
        Queue::fake();
        $content = $this->createContent('eval_short');
        StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban lama',
            'ai_feedback' => 'Feedback lama',
            'ai_status' => StudentAnswer::AI_STATUS_COMPLETED,
            'answer_version' => 3,
            'evaluation' => 'benar',
        ]);

        $response = $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $content->id,
                'answer_text' => 'Jawaban baru',
            ],
        );

        $response->assertStatus(202)->assertJson([
            'ai_status' => StudentAnswer::AI_STATUS_QUEUED,
            'answer_version' => 4,
        ]);
        $this->assertDatabaseHas('student_answers', [
            'content_id' => $content->id,
            'answer_data' => 'Jawaban baru',
            'ai_feedback' => null,
            'answer_version' => 4,
            'evaluation' => null,
        ]);
    }

    public function test_resaving_an_unchanged_answer_keeps_the_teacher_evaluation(): void
    {
        Queue::fake();
        $content = $this->createContent('eval_short');
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban yang sama',
            'evaluation' => 'benar',
            'ai_status' => StudentAnswer::AI_STATUS_COMPLETED,
            'answer_version' => 1,
        ]);

        $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $content->id,
                'answer_text' => 'Jawaban yang sama',
            ],
        )->assertStatus(202);

        $this->assertSame('benar', $answer->fresh()->evaluation);
    }

    public function test_content_from_another_phase_cannot_be_written(): void
    {
        Queue::fake();
        $otherPhase = TopicPhase::create([
            'topic_id' => $this->topic->id,
            'name' => 'Other phase',
            'order' => 2,
            'is_ai_enabled' => true,
        ]);
        $foreignContent = PhaseContent::create([
            'topic_phase_id' => $otherPhase->id,
            'type' => 'eval_essay',
            'content_data' => ['question' => 'Pertanyaan lain'],
            'order' => 1,
        ]);

        $this->actingAs($this->student)->postJson(
            route('siswa.answers.store', $this->phase),
            [
                'content_id' => $foreignContent->id,
                'answer_text' => 'Percobaan jawaban.',
            ],
        )->assertNotFound();

        $this->assertDatabaseMissing('student_answers', [
            'user_id' => $this->student->id,
            'content_id' => $foreignContent->id,
        ]);
    }

    public function test_feedback_status_only_returns_the_authenticated_students_answers(): void
    {
        $content = $this->createContent('eval_essay');
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('SISWA');

        StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban sendiri',
            'ai_feedback' => 'Feedback sendiri',
            'ai_status' => StudentAnswer::AI_STATUS_COMPLETED,
            'answer_version' => 2,
        ]);

        $response = $this->actingAs($this->student)->getJson(
            route('siswa.answers.ai-feedback-status', [
                'phase' => $this->phase,
                'content_ids' => [$content->id],
            ]),
        );

        $response->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertJsonPath("items.{$content->id}.status", StudentAnswer::AI_STATUS_COMPLETED)
            ->assertJsonPath("items.{$content->id}.feedback", 'Feedback sendiri')
            ->assertJsonPath("items.{$content->id}.answer_version", 2);
    }

    public function test_stale_job_version_does_not_replace_current_feedback(): void
    {
        $content = $this->createContent('eval_essay');
        $answer = StudentAnswer::create([
            'user_id' => $this->student->id,
            'phase_id' => $this->phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Jawaban versi dua',
            'ai_feedback' => null,
            'ai_status' => StudentAnswer::AI_STATUS_QUEUED,
            'answer_version' => 2,
        ]);

        Cache::put(
            'ai_eval_v1:'.md5($content->id.':'.'prompt'.':'.'jawaban versi dua'),
            'Feedback yang tidak boleh dipakai',
        );

        (new EvaluateStudentAnswerJob($answer, 'prompt', 1))->handle();

        $answer->refresh();
        $this->assertNull($answer->ai_feedback);
        $this->assertSame(StudentAnswer::AI_STATUS_QUEUED, $answer->ai_status);
        $this->assertSame(2, $answer->answer_version);
    }

    private function createContent(string $type): PhaseContent
    {
        return PhaseContent::create([
            'topic_phase_id' => $this->phase->id,
            'type' => $type,
            'content_data' => [
                'question' => 'Jelaskan jawaban Anda.',
                'options' => ['Pilihan A', 'Pilihan B'],
            ],
            'order' => 1,
        ]);
    }
}
