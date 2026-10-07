<?php

namespace Tests\Feature\Guru;

use App\Models\AiChatLog;
use App\Models\Classroom;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiChatLogScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_only_sees_logs_recorded_for_the_selected_classroom(): void
    {
        [$teacher, $classroom, , $expectedLog] = $this->createLogsAcrossClasses();

        $this->actingAs($teacher)
            ->get(route('guru.classes.ai-chat-logs.index', $classroom))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Guru/Classes/Show')
                ->has('chatLogs.data', 1)
                ->where('chatLogs.data.0.id', $expectedLog->id)
                ->where('chatLogs.data.0.classroom_id', $classroom->id));
    }

    public function test_printed_logs_are_scoped_to_the_selected_classroom(): void
    {
        [$teacher, $classroom, , $expectedLog] = $this->createLogsAcrossClasses();
        $document = Mockery::mock(PdfDocument::class);

        $document->shouldReceive('stream')
            ->once()
            ->andReturn(response('pdf-content'));

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($classroom, $expectedLog): bool {
                $this->assertSame('print.chat-logs', $view);
                $this->assertSame($classroom->id, $data['classroom']->id);
                $this->assertSame([$expectedLog->id], $data['chatLogs']->pluck('id')->all());

                return true;
            })
            ->andReturn($document);

        $this->actingAs($teacher)
            ->get(route('guru.classes.print.chat-logs', $classroom))
            ->assertOk()
            ->assertSee('pdf-content');
    }

    private function createLogsAcrossClasses(): array
    {
        Role::findOrCreate('GURU', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('GURU');
        $student = User::factory()->create();

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'class_name' => 'Kelas Utama',
            'class_code' => 'LOG01',
        ]);
        $otherClassroom = Classroom::create([
            'teacher_id' => $teacher->id,
            'class_name' => 'Kelas Lain',
            'class_code' => 'LOG02',
        ]);
        $classroom->students()->attach($student->id);
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

        return [$teacher, $classroom, $otherClassroom, $expectedLog];
    }
}
