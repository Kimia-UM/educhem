<?php

namespace Tests\Feature;

use App\Models\PhaseContent;
use App\Models\StudentAnswer;
use App\Models\TopicPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoadTestDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_repeatable_synthetic_load_test_data(): void
    {
        $password = 'LoadTestPassword!123';

        $this->artisan('load-test:prepare', [
            '--students' => 2,
            '--email-prefix' => 'load.student',
            '--email-domain' => 'load.educhem.test',
        ])
            ->expectsQuestion(
                'Shared password for synthetic students (minimum 12 characters)',
                $password,
            )
            ->expectsConfirmation(
                'Create or update 2 synthetic students for load-test run default?',
                'yes',
            )
            ->assertSuccessful();

        $this->artisan('load-test:prepare', [
            '--students' => 2,
            '--email-prefix' => 'load.student',
            '--email-domain' => 'load.educhem.test',
        ])
            ->expectsQuestion(
                'Shared password for synthetic students (minimum 12 characters)',
                $password,
            )
            ->expectsConfirmation(
                'Create or update 2 synthetic students for load-test run default?',
                'yes',
            )
            ->assertSuccessful();

        $student = User::where('email', 'load.student.001@load.educhem.test')->firstOrFail();

        $this->assertTrue(Hash::check($password, $student->password));
        $this->assertTrue($student->hasRole('SISWA'));
        $this->assertDatabaseCount('class_members', 2);
        $this->assertDatabaseHas('classes', ['class_name' => '[LOAD TEST:default] 2 Concurrent Students']);
        $this->assertDatabaseHas('topics', ['title' => '[LOAD TEST:default] Autosave and AI Queue']);
        $this->assertDatabaseCount('topic_phases', 2);
        $this->assertDatabaseCount('phase_contents', 3);
        $this->assertDatabaseCount('student_answers', 0);
        $this->assertDatabaseHas('topic_phases', [
            'name' => '[LOAD TEST:default] AI Queue',
            'is_ai_enabled' => true,
            'is_chatbot_enabled' => true,
        ]);

        $this->actingAs($student)
            ->get(route('siswa.dashboard'))
            ->assertOk();

        $phase = TopicPhase::query()
            ->where('name', '[LOAD TEST:default] Web Autosave')
            ->firstOrFail();
        $content = PhaseContent::query()
            ->where('topic_phase_id', $phase->id)
            ->firstOrFail();

        StudentAnswer::create([
            'user_id' => $student->id,
            'phase_id' => $phase->id,
            'content_id' => $content->id,
            'answer_data' => 'Synthetic dashboard regression answer.',
            'ai_status' => StudentAnswer::AI_STATUS_NOT_REQUIRED,
        ]);

        $this->actingAs($student)
            ->get(route('siswa.dashboard'))
            ->assertOk();

        $this->artisan('load-test:cleanup', [
            '--email-prefix' => 'load.student',
            '--email-domain' => 'load.educhem.test',
            '--run-id' => 'default',
        ])
            ->expectsConfirmation(
                'Delete synthetic data for load-test run default?',
                'yes',
            )
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'load.student.001@load.educhem.test']);
        $this->assertDatabaseCount('classes', 0);
        $this->assertDatabaseCount('topics', 0);
        $this->assertDatabaseCount('student_answers', 0);
    }
}
