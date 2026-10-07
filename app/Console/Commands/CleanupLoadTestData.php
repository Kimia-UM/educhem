<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupLoadTestData extends Command
{
    protected $signature = 'load-test:cleanup
        {--email-prefix=load.student : Prefix used for synthetic student emails}
        {--email-domain=load.educhem.test : Domain used for synthetic student emails}
        {--run-id=default : Identifier used for the load-test run}
        {--allow-production : Explicitly allow cleanup in production}
        {--force : Skip the interactive confirmation}';

    protected $description = 'Delete only the isolated synthetic records created for a load-test run';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Refusing production cleanup without --allow-production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'staging', 'testing', 'production'])) {
            $this->error('Refusing to clean load-test data in this environment.');

            return self::FAILURE;
        }

        $emailPrefix = strtolower(trim((string) $this->option('email-prefix')));
        $emailDomain = strtolower(trim((string) $this->option('email-domain')));
        $runId = strtolower(trim((string) $this->option('run-id')));

        if (! preg_match('/^[a-z0-9._-]+$/', $emailPrefix)
            || ! filter_var("{$emailPrefix}.001@{$emailDomain}", FILTER_VALIDATE_EMAIL)
            || ! preg_match('/^[a-z0-9-]{1,20}$/', $runId)) {
            $this->error('The email prefix, email domain, or run ID is invalid.');

            return self::INVALID;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Delete synthetic data for load-test run {$runId}?",
            false,
        )) {
            $this->warn('No load-test data was deleted.');

            return self::SUCCESS;
        }

        $label = "[LOAD TEST:{$runId}]";
        $classCode = 'L'.strtoupper(substr(hash('sha256', $runId), 0, 5));
        $emailPattern = '/^'.preg_quote($emailPrefix, '/').'\.(?:teacher|\d{3})@'.preg_quote($emailDomain, '/').'$/D';

        $users = User::query()
            ->where('email', 'like', '%@'.$emailDomain)
            ->get(['id', 'email'])
            ->filter(fn (User $user): bool => preg_match($emailPattern, $user->email) === 1);

        $classroom = Classroom::query()->where('class_code', $classCode)->first();
        if ($classroom && ! str_starts_with((string) $classroom->description, $label)) {
            $this->error("Class code {$classCode} exists without the expected load-test marker; refusing cleanup.");

            return self::FAILURE;
        }

        $topic = Topic::query()->where('title', "{$label} Autosave and AI Queue")->first();

        $counts = DB::transaction(function () use ($classroom, $topic, $users): array {
            $userIds = $users->pluck('id');
            $emails = $users->pluck('email');

            if ($userIds->isNotEmpty()) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }

            if ($emails->isNotEmpty()) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }

            $classDeleted = $classroom?->delete() ? 1 : 0;
            $topicDeleted = $topic?->delete() ? 1 : 0;
            $usersDeleted = $userIds->isEmpty()
                ? 0
                : User::query()->whereKey($userIds->all())->delete();

            return compact('classDeleted', 'topicDeleted', 'usersDeleted');
        });

        $this->info('Synthetic load-test data was removed.');
        $this->table(['Record type', 'Deleted'], [
            ['users', $counts['usersDeleted']],
            ['classes', $counts['classDeleted']],
            ['topics', $counts['topicDeleted']],
        ]);

        return self::SUCCESS;
    }
}
