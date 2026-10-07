<?php

namespace Tests\Feature\Siswa;

use App\Ai\Agents\ChemistryTutorAgent;
use App\Jobs\ProcessAiChatJob;
use App\Models\AiChatLog;
use App\Models\Classroom;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\User;
use App\Services\AiChatDirectSlotPool;
use App\Services\AiChatProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatbotQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_slot_pool_enforces_the_configured_limit(): void
    {
        config()->set('ai.chatbot.direct_slots', 4);
        $pool = app(AiChatDirectSlotPool::class);
        $locks = [];

        try {
            for ($slot = 0; $slot < 4; $slot++) {
                $lock = $pool->acquire();
                $this->assertNotNull($lock);
                $locks[] = $lock;
            }

            $this->assertNull($pool->acquire());
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }
    }

    public function test_cache_miss_uses_direct_slot_and_returns_ai_response(): void
    {
        Queue::fake();
        ChemistryTutorAgent::fake(['Mol adalah satuan jumlah zat.']);
        config()->set('ai.chatbot.direct_slots', 4);
        config()->set('ai.chatbot.direct_timeout', 12);
        [$student, $phase, $classroom] = $this->createAccessiblePhase();

        $response = $this->actingAs($student)->postJson(route('siswa.chatbot.store'), [
            'prompt' => 'Apa itu mol?',
            'topic_context' => 'Stoikiometri',
            'phase_id' => $phase->id,
            'classroom_id' => $classroom->id,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'cached' => false,
            'processing_mode' => 'direct',
            'response' => 'Mol adalah satuan jumlah zat.',
        ]);
        Queue::assertNothingPushed();
        $this->assertDatabaseHas('ai_chat_logs', [
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => AiChatLog::STATUS_COMPLETED,
            'processing_mode' => 'direct',
            'response' => 'Mol adalah satuan jumlah zat.',
        ]);
        $this->assertNotNull(AiChatLog::firstOrFail()->queue_wait_ms);
        $this->assertNotNull(AiChatLog::firstOrFail()->provider_duration_ms);
        $this->assertNotNull(AiChatLog::firstOrFail()->total_duration_ms);
    }

    public function test_cache_miss_is_queued_when_all_direct_slots_are_busy(): void
    {
        Queue::fake();
        config()->set('ai.chatbot.direct_slots', 1);
        $occupiedSlot = Cache::lock('ai_chat_direct_slot:1', 30);
        $this->assertTrue($occupiedSlot->get());
        [$student, $phase, $classroom] = $this->createAccessiblePhase();

        try {
            $response = $this->actingAs($student)->postJson(route('siswa.chatbot.store'), [
                'prompt' => 'Apa itu mol?',
                'topic_context' => 'Stoikiometri',
                'phase_id' => $phase->id,
                'classroom_id' => $classroom->id,
            ]);
        } finally {
            $occupiedSlot->release();
        }

        $response->assertStatus(202)->assertJson([
            'status' => 'queued',
            'fallback' => false,
        ]);
        Queue::assertPushed(
            ProcessAiChatJob::class,
            fn (ProcessAiChatJob $job) => $job->queue === 'ai-chat',
        );
        $this->assertDatabaseHas('ai_chat_logs', [
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => AiChatLog::STATUS_QUEUED,
            'response' => null,
        ]);
    }

    public function test_direct_timeout_falls_back_to_the_chat_queue(): void
    {
        Queue::fake();
        config()->set('ai.chatbot.direct_slots', 1);
        ChemistryTutorAgent::fake([
            fn () => throw new RuntimeException('Request timed out.'),
        ]);
        [$student, $phase, $classroom] = $this->createAccessiblePhase();

        $response = $this->actingAs($student)->postJson(route('siswa.chatbot.store'), [
            'prompt' => 'Jelaskan konsep mol.',
            'topic_context' => 'Stoikiometri',
            'phase_id' => $phase->id,
            'classroom_id' => $classroom->id,
        ]);

        $response->assertStatus(202)->assertJson([
            'status' => 'queued',
            'fallback' => true,
        ]);
        Queue::assertPushed(
            ProcessAiChatJob::class,
            fn (ProcessAiChatJob $job) => $job->queue === 'ai-chat',
        );
        $this->assertDatabaseHas('ai_chat_logs', [
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'status' => AiChatLog::STATUS_QUEUED,
            'processing_mode' => 'queue_fallback',
            'error_code' => 'direct_timeout',
        ]);
        $this->assertNotNull(AiChatLog::firstOrFail()->direct_attempt_ms);
    }

    public function test_queued_job_records_queue_provider_and_total_durations(): void
    {
        ChemistryTutorAgent::fake(['Ion adalah atom atau molekul bermuatan.']);
        $student = User::factory()->create();
        $chatLog = AiChatLog::create([
            'user_id' => $student->id,
            'prompt' => 'Apa itu ion?',
            'status' => AiChatLog::STATUS_QUEUED,
            'enqueued_at_ms' => AiChatProcessor::nowMilliseconds() - 500,
        ]);

        $job = new ProcessAiChatJob($chatLog, 'Struktur atom');
        $job->handle(app(AiChatProcessor::class));
        $chatLog->refresh();

        $this->assertSame('ai-chat', $job->queue);
        $this->assertSame(AiChatLog::STATUS_COMPLETED, $chatLog->status);
        $this->assertSame('queue', $chatLog->processing_mode);
        $this->assertSame('Ion adalah atom atau molekul bermuatan.', $chatLog->response);
        $this->assertGreaterThanOrEqual(500, $chatLog->queue_wait_ms);
        $this->assertNotNull($chatLog->provider_duration_ms);
        $this->assertGreaterThanOrEqual(500, $chatLog->total_duration_ms);
    }

    public function test_cache_hit_is_returned_immediately(): void
    {
        Queue::fake();
        [$student, $phase, $classroom] = $this->createAccessiblePhase();
        $cacheKey = 'ai_chat_v1:'.md5('Stoikiometri'.':'.'Bantu siswa'.':'.'apa itu mol?');
        Cache::put($cacheKey, 'Mol adalah satuan jumlah zat.');

        $response = $this->actingAs($student)->postJson(route('siswa.chatbot.store'), [
            'prompt' => 'Apa itu mol?',
            'topic_context' => 'Stoikiometri',
            'phase_id' => $phase->id,
            'classroom_id' => $classroom->id,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'cached' => true,
            'response' => 'Mol adalah satuan jumlah zat.',
        ]);
        Queue::assertNothingPushed();
        $this->assertDatabaseHas('ai_chat_logs', [
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'processing_mode' => 'cache',
        ]);
    }

    public function test_chat_history_is_scoped_to_the_requested_classroom(): void
    {
        [$student, , $classroom] = $this->createAccessiblePhase();
        $otherClassroom = Classroom::create([
            'teacher_id' => $classroom->teacher_id,
            'class_name' => 'Kelas Lain',
            'class_code' => 'CHAT02',
        ]);
        $otherClassroom->students()->attach($student->id);

        $expectedLog = AiChatLog::create([
            'user_id' => $student->id,
            'classroom_id' => $classroom->id,
            'prompt' => 'Pertanyaan kelas utama',
            'response' => 'Jawaban utama',
        ]);
        AiChatLog::create([
            'user_id' => $student->id,
            'classroom_id' => $otherClassroom->id,
            'prompt' => 'Pertanyaan kelas lain',
            'response' => 'Jawaban lain',
        ]);
        AiChatLog::create([
            'user_id' => $student->id,
            'classroom_id' => null,
            'prompt' => 'Log lama tanpa kelas',
            'response' => 'Jawaban lama',
        ]);

        $this->actingAs($student)
            ->getJson(route('siswa.chatbot.index', [
                'classroom_id' => $classroom->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $expectedLog->id);
    }

    public function test_phase_must_belong_to_the_submitted_classroom(): void
    {
        Queue::fake();
        [$student, $phase, $classroom] = $this->createAccessiblePhase();
        $otherClassroom = Classroom::create([
            'teacher_id' => $classroom->teacher_id,
            'class_name' => 'Kelas Tanpa Topik',
            'class_code' => 'CHAT03',
        ]);
        $otherClassroom->students()->attach($student->id);

        $this->actingAs($student)
            ->postJson(route('siswa.chatbot.store'), [
                'prompt' => 'Apa itu mol?',
                'topic_context' => 'Stoikiometri',
                'phase_id' => $phase->id,
                'classroom_id' => $otherClassroom->id,
            ])
            ->assertForbidden();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('ai_chat_logs', 0);
    }

    public function test_status_endpoint_returns_only_the_authenticated_students_log(): void
    {
        [$student] = $this->createAccessiblePhase();
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('SISWA');
        $log = AiChatLog::create([
            'user_id' => $student->id,
            'prompt' => 'Apa rumus air?',
            'response' => 'H₂O',
            'status' => AiChatLog::STATUS_COMPLETED,
            'processing_mode' => 'direct',
            'queue_wait_ms' => 2,
            'provider_duration_ms' => 900,
            'total_duration_ms' => 910,
            'completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->getJson(route('siswa.chatbot.status', $log))
            ->assertOk()
            ->assertJson([
                'log_id' => $log->id,
                'status' => AiChatLog::STATUS_COMPLETED,
                'response' => 'H₂O',
                'processing_mode' => 'direct',
                'queue_wait_ms' => 2,
                'provider_duration_ms' => 900,
                'total_duration_ms' => 910,
            ])
            ->assertJsonMissingPath('prompt');

        $this->actingAs($otherStudent)
            ->getJson(route('siswa.chatbot.status', $log))
            ->assertNotFound();
    }

    private function createAccessiblePhase(): array
    {
        Role::findOrCreate('SISWA', 'web');
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $student->assignRole('SISWA');
        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'class_name' => 'Kelas Chat',
            'class_code' => 'CHAT01',
        ]);
        $classroom->students()->attach($student->id);
        $topic = Topic::create([
            'title' => 'Stoikiometri',
            'is_published' => true,
        ]);
        $classroom->topics()->attach($topic->id, ['is_open' => true]);
        $phase = TopicPhase::create([
            'topic_id' => $topic->id,
            'name' => 'Chat phase',
            'order' => 1,
            'is_chatbot_enabled' => true,
            'chatbot_prompt_setting' => 'Bantu siswa',
        ]);

        return [$student, $phase, $classroom];
    }
}
