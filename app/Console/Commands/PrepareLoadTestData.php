<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\PhaseContent;
use App\Models\Topic;
use App\Models\TopicPhase;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PrepareLoadTestData extends Command
{
    protected $signature = 'load-test:prepare
        {--students=50 : Number of synthetic student accounts to create (1-100)}
        {--email-prefix=load.student : Prefix used for synthetic student emails}
        {--email-domain=load.educhem.test : Domain used for synthetic student emails}
        {--run-id=default : Identifier used to isolate this load-test run}
        {--allow-production : Explicitly allow synthetic data in production}
        {--force : Skip the interactive confirmation}';

    protected $description = 'Create isolated synthetic accounts and worksheet data for staging load tests';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Refusing production without --allow-production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'staging', 'testing', 'production'])) {
            $this->error('Refusing to create load-test data in this environment.');

            return self::FAILURE;
        }

        $studentCount = (int) $this->option('students');
        $emailPrefix = strtolower(trim((string) $this->option('email-prefix')));
        $emailDomain = strtolower(trim((string) $this->option('email-domain')));
        $runId = strtolower(trim((string) $this->option('run-id')));

        if ($studentCount < 1 || $studentCount > 100) {
            $this->error('The --students option must be between 1 and 100.');

            return self::INVALID;
        }

        if (! preg_match('/^[a-z0-9._-]+$/', $emailPrefix)
            || ! filter_var("{$emailPrefix}.001@{$emailDomain}", FILTER_VALIDATE_EMAIL)) {
            $this->error('The email prefix or domain is invalid.');

            return self::INVALID;
        }

        if (! preg_match('/^[a-z0-9-]{1,20}$/', $runId)) {
            $this->error('The --run-id option must contain 1-20 lowercase letters, numbers, or hyphens.');

            return self::INVALID;
        }

        $password = (string) (getenv('LOAD_TEST_PASSWORD') ?: '');

        if ($password === '' && $this->input->isInteractive()) {
            $password = (string) $this->secret('Shared password for synthetic students (minimum 12 characters)');
        }

        if (mb_strlen($password) < 12) {
            $this->error('The synthetic student password must contain at least 12 characters.');

            return self::INVALID;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Create or update {$studentCount} synthetic students for load-test run {$runId}?",
            false,
        )) {
            $this->warn('No load-test data was changed.');

            return self::SUCCESS;
        }

        $result = DB::transaction(function () use (
            $emailDomain,
            $emailPrefix,
            $password,
            $runId,
            $studentCount,
        ): array {
            $label = "[LOAD TEST:{$runId}]";
            $classCode = 'L'.strtoupper(substr(hash('sha256', $runId), 0, 5));
            $teacherRole = Role::findOrCreate('GURU', 'web');
            $studentRole = Role::findOrCreate('SISWA', 'web');

            $teacher = User::firstOrNew([
                'email' => "{$emailPrefix}.teacher@{$emailDomain}",
            ]);

            $teacherAttributes = [
                'name' => "{$label} Teacher",
                'email_verified_at' => now(),
                'status' => true,
            ];

            if (! $teacher->exists) {
                $teacherAttributes['password'] = Str::password(40);
            }

            $teacher->forceFill($teacherAttributes)->save();
            $teacher->syncRoles([$teacherRole]);

            $existingClassroom = Classroom::query()->where('class_code', $classCode)->first();
            if ($existingClassroom && ! str_starts_with((string) $existingClassroom->description, $label)) {
                throw new \RuntimeException(
                    "Class code {$classCode} exists without the expected load-test marker.",
                );
            }

            $classroom = Classroom::updateOrCreate(
                ['class_code' => $classCode],
                [
                    'teacher_id' => $teacher->id,
                    'class_name' => "{$label} {$studentCount} Concurrent Students",
                    'description' => "{$label} Synthetic isolated class. Safe to delete after the test.",
                ],
            );

            $studentIds = [];

            for ($number = 1; $number <= $studentCount; $number++) {
                $email = sprintf('%s.%03d@%s', $emailPrefix, $number, $emailDomain);
                $student = User::firstOrNew(['email' => $email]);
                $student->forceFill([
                    'name' => sprintf('%s Student %03d', $label, $number),
                    'password' => $password,
                    'email_verified_at' => now(),
                    'status' => true,
                ])->save();
                $student->syncRoles([$studentRole]);
                $studentIds[] = $student->id;
            }

            $classroom->students()->syncWithoutDetaching($studentIds);

            DB::table('class_members')
                ->where('class_id', $classroom->id)
                ->whereIn('user_id', $studentIds)
                ->update([
                    'is_evaluation_sent' => false,
                    'is_evaluation_finished' => false,
                    'pre_test_score' => null,
                    'post_test_score' => null,
                    'updated_at' => now(),
                ]);

            $topic = Topic::updateOrCreate(
                ['title' => "{$label} Autosave and AI Queue"],
                [
                    'description' => "{$label} Synthetic isolated topic for repeatable performance testing.",
                    'is_published' => true,
                ],
            );

            $classroom->topics()->syncWithoutDetaching([
                $topic->id => ['is_open' => true],
            ]);
            $classroom->topics()->updateExistingPivot($topic->id, ['is_open' => true]);

            $webPhase = TopicPhase::updateOrCreate(
                [
                    'topic_id' => $topic->id,
                    'name' => "{$label} Web Autosave",
                ],
                [
                    'description' => 'AI disabled so 50-user web capacity can be measured without provider cost.',
                    'order' => 1,
                    'is_ai_enabled' => false,
                    'is_chatbot_enabled' => false,
                    'ai_prompt_setting' => null,
                    'chatbot_prompt_setting' => null,
                ],
            );

            $mcqContent = PhaseContent::updateOrCreate(
                ['topic_phase_id' => $webPhase->id, 'order' => 1],
                [
                    'type' => 'eval_mcq',
                    'content_data' => [
                        'question' => 'Manakah rumus kimia air?',
                        'options' => ['H2O', 'CO2', 'NaCl', 'O2'],
                    ],
                    'correct_answers' => [0],
                ],
            );

            $essayContent = PhaseContent::updateOrCreate(
                ['topic_phase_id' => $webPhase->id, 'order' => 2],
                [
                    'type' => 'eval_essay',
                    'content_data' => [
                        'question' => 'Jelaskan secara singkat mengapa air merupakan senyawa.',
                    ],
                    'correct_answers' => null,
                ],
            );

            $aiPhase = TopicPhase::updateOrCreate(
                [
                    'topic_id' => $topic->id,
                    'name' => "{$label} AI Queue",
                ],
                [
                    'description' => 'Use only for isolated AI evaluation and hybrid chatbot load tests.',
                    'order' => 2,
                    'is_ai_enabled' => true,
                    'is_chatbot_enabled' => true,
                    'ai_prompt_setting' => 'Berikan feedback kimia yang singkat dan jelas.',
                    'chatbot_prompt_setting' => 'Jawab singkat, akurat, dan hanya tentang kimia.',
                ],
            );

            $aiContent = PhaseContent::updateOrCreate(
                ['topic_phase_id' => $aiPhase->id, 'order' => 1],
                [
                    'type' => 'eval_short',
                    'content_data' => [
                        'question' => 'Apa perbedaan atom dan molekul?',
                    ],
                    'correct_answers' => null,
                ],
            );

            return compact(
                'aiContent',
                'aiPhase',
                'classroom',
                'essayContent',
                'mcqContent',
                'topic',
                'webPhase',
            );
        });

        $this->newLine();
        $this->info('Synthetic load-test data is ready. Keep the password outside Git and chat.');
        $this->table(['Setting', 'Value'], [
            ['BASE_URL', config('app.url')],
            ['RUN_ID', $runId],
            ['STUDENT_COUNT', $studentCount],
            ['EMAIL_PREFIX', $emailPrefix],
            ['EMAIL_DOMAIN', $emailDomain],
            ['CLASS_CODE', $result['classroom']->class_code],
            ['CLASSROOM_ID', $result['classroom']->id],
            ['TOPIC_ID', $result['topic']->id],
            ['WEB_PHASE_ID', $result['webPhase']->id],
            ['MCQ_CONTENT_ID', $result['mcqContent']->id],
            ['ESSAY_CONTENT_ID', $result['essayContent']->id],
            ['AI_PHASE_ID', $result['aiPhase']->id],
            ['AI_CONTENT_ID', $result['aiContent']->id],
        ]);

        return self::SUCCESS;
    }
}
