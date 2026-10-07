<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

class BenchmarkGeminiModels extends Command
{
    protected $signature = 'gemini:benchmark-models
        {--list-only : List models available to the configured API key without generating content}
        {--models= : Comma-separated model IDs to benchmark; defaults to stable text-generation models}
        {--attempts=3 : Number of generation requests per model (1-20)}
        {--timeout=120 : Timeout for each request in seconds (5-300)}
        {--delay-ms=500 : Delay between requests in milliseconds (0-5000)}
        {--max-output-tokens=512 : Maximum output tokens per request (128-2048)}
        {--max-models=12 : Maximum automatically selected models (1-50)}
        {--include-preview : Include preview, latest, and experimental models in automatic selection}
        {--allow-production : Explicitly allow billable generation requests in production}
        {--json= : Optional path for a response-free JSON benchmark report}';

    protected $description = 'List accessible Gemini models and benchmark text-generation stability and latency';

    private const BENCHMARK_PROMPT = 'Jawab tepat satu kata saja. Apa rumus kimia air?';

    public function handle(): int
    {
        $apiKey = (string) config('ai.providers.gemini.key');
        $baseUrl = rtrim((string) config('ai.providers.gemini.url'), '/');

        if ($apiKey === '') {
            $this->error('GEMINI_API_KEY is not configured.');

            return self::FAILURE;
        }

        if ($baseUrl === '') {
            $this->error('The Gemini API base URL is not configured.');

            return self::FAILURE;
        }

        $validated = $this->validateNumericOptions();
        if ($validated === null) {
            return self::INVALID;
        }

        try {
            $models = $this->fetchModels($baseUrl, $apiKey, $validated['timeout']);
        } catch (Throwable $exception) {
            $this->error($this->safeExceptionMessage($exception, $apiKey));

            return self::FAILURE;
        }

        if ($models === []) {
            $this->error('The API key returned no models.');

            return self::FAILURE;
        }

        $configuredModel = $this->normalizeModelId(
            (string) config('ai.providers.gemini.models.text.default'),
        );

        $this->info(sprintf(
            'Gemini returned %d accessible models. Configured application model: %s.',
            count($models),
            $configuredModel !== '' ? $configuredModel : '(not configured)',
        ));

        if ((bool) $this->option('list-only')) {
            $this->renderModelList($models, $configuredModel);

            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Refusing billable production benchmark without --allow-production.');

            return self::FAILURE;
        }

        $selectedModels = $this->selectModels(
            $models,
            $configuredModel,
            $validated['max_models'],
            (bool) $this->option('include-preview'),
        );

        if ($selectedModels === null) {
            return self::INVALID;
        }

        if ($selectedModels === []) {
            $this->error('No eligible text-generation models were selected. Use --list-only to inspect availability.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            'Benchmarking %d models × %d attempts (%d total billable requests).',
            count($selectedModels),
            $validated['attempts'],
            count($selectedModels) * $validated['attempts'],
        ));
        $this->line('Responses are validated for a non-empty chemistry answer; response text and API keys are never printed or stored.');

        $measurements = $this->benchmark(
            $selectedModels,
            $baseUrl,
            $apiKey,
            $validated['attempts'],
            $validated['timeout'],
            $validated['delay_ms'],
            $validated['max_output_tokens'],
        );
        $summaries = $this->summarize($selectedModels, $measurements);

        $this->renderSummary($summaries, $configuredModel);

        if ($jsonPath = trim((string) $this->option('json'))) {
            $this->writeJsonReport(
                $jsonPath,
                $summaries,
                $configuredModel,
                $validated['attempts'],
                $validated['max_output_tokens'],
            );
        }

        return collect($summaries)->contains(fn (array $summary): bool => $summary['successes'] > 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @return array{attempts: int, timeout: int, delay_ms: int, max_output_tokens: int, max_models: int}|null
     */
    private function validateNumericOptions(): ?array
    {
        $values = [
            'attempts' => (int) $this->option('attempts'),
            'timeout' => (int) $this->option('timeout'),
            'delay_ms' => (int) $this->option('delay-ms'),
            'max_output_tokens' => (int) $this->option('max-output-tokens'),
            'max_models' => (int) $this->option('max-models'),
        ];

        $valid = $values['attempts'] >= 1 && $values['attempts'] <= 20
            && $values['timeout'] >= 5 && $values['timeout'] <= 300
            && $values['delay_ms'] >= 0 && $values['delay_ms'] <= 5000
            && $values['max_output_tokens'] >= 128 && $values['max_output_tokens'] <= 2048
            && $values['max_models'] >= 1 && $values['max_models'] <= 50;

        if (! $valid) {
            $this->error('Invalid options: attempts 1-20, timeout 5-300, delay-ms 0-5000, max-output-tokens 128-2048, and max-models 1-50 are required.');

            return null;
        }

        return $values;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchModels(string $baseUrl, string $apiKey, int $timeout): array
    {
        $models = [];
        $pageToken = null;

        for ($page = 0; $page < 20; $page++) {
            $query = ['pageSize' => 1000];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }

            $response = $this->client($apiKey, $timeout)
                ->get("{$baseUrl}/models", $query);

            if (! $response->successful()) {
                throw new \RuntimeException(sprintf(
                    'Gemini models.list failed with HTTP %d (%s).',
                    $response->status(),
                    (string) ($response->json('error.status') ?? 'unknown_error'),
                ));
            }

            foreach ((array) $response->json('models', []) as $model) {
                if (! is_array($model)) {
                    continue;
                }

                $model['id'] = $this->normalizeModelId((string) ($model['name'] ?? ''));
                if ($model['id'] !== '') {
                    $models[$model['id']] = $model;
                }
            }

            $pageToken = $response->json('nextPageToken');
            if (! is_string($pageToken) || $pageToken === '') {
                break;
            }
        }

        ksort($models, SORT_NATURAL);

        return array_values($models);
    }

    /**
     * @param  array<int, array<string, mixed>>  $models
     */
    private function renderModelList(array $models, string $configuredModel): void
    {
        $this->table(
            ['Model', 'generateContent', 'Input limit', 'Output limit', 'Category'],
            array_map(function (array $model) use ($configuredModel): array {
                $id = (string) $model['id'];

                return [
                    $id.($id === $configuredModel ? ' *' : ''),
                    $this->supportsGenerateContent($model) ? 'yes' : 'no',
                    $model['inputTokenLimit'] ?? '-',
                    $model['outputTokenLimit'] ?? '-',
                    $this->modelCategory($id),
                ];
            }, $models),
        );

        $this->line('* configured application model');
    }

    /**
     * @param  array<int, array<string, mixed>>  $availableModels
     * @return array<int, string>|null
     */
    private function selectModels(
        array $availableModels,
        string $configuredModel,
        int $maxModels,
        bool $includePreview,
    ): ?array {
        $availableById = collect($availableModels)->keyBy('id');
        $requested = collect(explode(',', (string) $this->option('models')))
            ->map(fn (string $model): string => $this->normalizeModelId(trim($model)))
            ->filter()
            ->unique()
            ->values();

        if ($requested->isNotEmpty()) {
            $unknown = $requested->reject(fn (string $id): bool => $availableById->has($id));
            if ($unknown->isNotEmpty()) {
                $this->error('Unavailable model(s) for this API key: '.$unknown->implode(', '));

                return null;
            }

            $unsupported = $requested->filter(
                fn (string $id): bool => ! $this->supportsGenerateContent($availableById->get($id)),
            );
            if ($unsupported->isNotEmpty()) {
                $this->error('Model(s) do not support generateContent: '.$unsupported->implode(', '));

                return null;
            }

            if ($requested->count() > $maxModels) {
                $this->error("Requested models exceed --max-models={$maxModels}.");

                return null;
            }

            return $requested->all();
        }

        $selected = collect($availableModels)
            ->filter(fn (array $model): bool => $this->supportsGenerateContent($model))
            ->pluck('id')
            ->filter(fn (string $id): bool => $this->isTextBenchmarkCandidate($id, $includePreview))
            ->values();

        if ($configuredModel !== ''
            && $availableById->has($configuredModel)
            && $this->supportsGenerateContent($availableById->get($configuredModel))
            && ! $selected->contains($configuredModel)) {
            $selected->prepend($configuredModel);
        }

        return $selected->unique()->take($maxModels)->values()->all();
    }

    /**
     * @param  array<int, string>  $models
     * @return array<string, array<int, array<string, int|bool|string|null>>>
     */
    private function benchmark(
        array $models,
        string $baseUrl,
        string $apiKey,
        int $attempts,
        int $timeout,
        int $delayMs,
        int $maxOutputTokens,
    ): array {
        $measurements = array_fill_keys($models, []);
        $requestNumber = 0;
        $requestTotal = count($models) * $attempts;

        for ($round = 0; $round < $attempts; $round++) {
            $orderedModels = $this->rotatedModels($models, $round);

            foreach ($orderedModels as $model) {
                $requestNumber++;
                $startedAt = hrtime(true);

                try {
                    $response = $this->client($apiKey, $timeout)
                        ->post("{$baseUrl}/models/{$model}:generateContent", [
                            'contents' => [[
                                'role' => 'user',
                                'parts' => [['text' => self::BENCHMARK_PROMPT]],
                            ]],
                            'generationConfig' => [
                                'temperature' => 0,
                                'candidateCount' => 1,
                                'maxOutputTokens' => $maxOutputTokens,
                            ],
                        ]);

                    $durationMs = $this->elapsedMilliseconds($startedAt);
                    $measurement = $this->measurementFromResponse($response, $durationMs);
                } catch (Throwable $exception) {
                    $measurement = [
                        'success' => false,
                        'duration_ms' => $this->elapsedMilliseconds($startedAt),
                        'http_status' => null,
                        'error_status' => class_basename($exception),
                        'total_tokens' => null,
                    ];
                }

                $measurements[$model][] = $measurement;
                $status = $measurement['success']
                    ? 'OK'
                    : sprintf(
                        'FAIL %s/%s',
                        $measurement['http_status'] ?? 'connection',
                        $measurement['error_status'] ?? 'unknown_error',
                    );

                $this->line(sprintf(
                    '[%d/%d] %s attempt %d/%d: %s, %.2f ms',
                    $requestNumber,
                    $requestTotal,
                    $model,
                    $round + 1,
                    $attempts,
                    $status,
                    $measurement['duration_ms'],
                ));

                if ($delayMs > 0 && $requestNumber < $requestTotal) {
                    usleep($delayMs * 1000);
                }
            }
        }

        return $measurements;
    }

    /**
     * @return array<string, int|bool|string|null>
     */
    private function measurementFromResponse(Response $response, float $durationMs): array
    {
        $text = collect((array) $response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter(fn (mixed $part): bool => is_string($part))
            ->implode(' ');
        $normalizedText = str_replace(['₂', ' '], ['2', ''], mb_strtoupper($text));
        $validAnswer = $text !== '' && str_contains($normalizedText, 'H2O');

        return [
            'success' => $response->successful() && $validAnswer,
            'duration_ms' => $durationMs,
            'http_status' => $response->status(),
            'error_status' => $response->successful()
                ? ($validAnswer ? null : 'invalid_or_empty_answer')
                : (string) ($response->json('error.status') ?? 'http_error'),
            'total_tokens' => is_numeric($response->json('usageMetadata.totalTokenCount'))
                ? (int) $response->json('usageMetadata.totalTokenCount')
                : null,
        ];
    }

    /**
     * @param  array<int, string>  $models
     * @param  array<string, array<int, array<string, int|bool|string|null>>>  $measurements
     * @return array<int, array<string, int|float|string>>
     */
    private function summarize(array $models, array $measurements): array
    {
        return collect($models)->map(function (string $model) use ($measurements): array {
            $modelMeasurements = $measurements[$model];
            $successes = collect($modelMeasurements)->where('success', true);
            $durations = $successes->pluck('duration_ms')->map(fn ($value): float => (float) $value)->all();
            $tokens = $successes->pluck('total_tokens')->filter(fn ($value): bool => is_int($value));
            $attempts = count($modelMeasurements);

            return [
                'model' => $model,
                'attempts' => $attempts,
                'successes' => $successes->count(),
                'success_rate' => $attempts > 0 ? round(($successes->count() / $attempts) * 100, 2) : 0.0,
                'median_ms' => $this->percentile($durations, 0.50),
                'p95_ms' => $this->percentile($durations, 0.95),
                'min_ms' => $durations === [] ? 0.0 : round(min($durations), 2),
                'max_ms' => $durations === [] ? 0.0 : round(max($durations), 2),
                'http_429' => collect($modelMeasurements)->where('http_status', 429)->count(),
                'http_404' => collect($modelMeasurements)->where('http_status', 404)->count(),
                'http_5xx' => collect($modelMeasurements)->filter(
                    fn (array $measurement): bool => is_int($measurement['http_status'])
                        && $measurement['http_status'] >= 500,
                )->count(),
                'invalid_output' => collect($modelMeasurements)
                    ->where('error_status', 'invalid_or_empty_answer')
                    ->count(),
                'average_tokens' => $tokens->isEmpty() ? 0.0 : round((float) $tokens->average(), 2),
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, int|float|string>>  $summaries
     */
    private function renderSummary(array $summaries, string $configuredModel): void
    {
        $this->newLine();
        $this->table(
            ['Model', 'Success', 'Median', 'P95', 'Min', 'Max', '404', '429', '5xx', 'Invalid', 'Avg tokens'],
            array_map(fn (array $summary): array => [
                $summary['model'].($summary['model'] === $configuredModel ? ' *' : ''),
                sprintf('%d/%d (%.2f%%)', $summary['successes'], $summary['attempts'], $summary['success_rate']),
                $this->milliseconds($summary['median_ms']),
                $this->milliseconds($summary['p95_ms']),
                $this->milliseconds($summary['min_ms']),
                $this->milliseconds($summary['max_ms']),
                $summary['http_404'],
                $summary['http_429'],
                $summary['http_5xx'],
                $summary['invalid_output'],
                $summary['average_tokens'],
            ], $summaries),
        );

        $stable = collect($summaries)
            ->sortBy([
                ['success_rate', 'desc'],
                ['p95_ms', 'asc'],
                ['median_ms', 'asc'],
            ])
            ->first();
        $topSuccessRate = $stable['success_rate'] ?? 0;
        $fastest = collect($summaries)
            ->where('success_rate', $topSuccessRate)
            ->sortBy('median_ms')
            ->first();

        if ($stable) {
            $this->info(sprintf(
                'Most stable in this run: %s (%.2f%% success, p95 %s).',
                $stable['model'],
                $stable['success_rate'],
                $this->milliseconds($stable['p95_ms']),
            ));
        }

        if ($fastest) {
            $this->info(sprintf(
                'Fastest within the top stability tier: %s (median %s).',
                $fastest['model'],
                $this->milliseconds($fastest['median_ms']),
            ));
        }

        if (collect($summaries)->max('attempts') < 10) {
            $this->warn('Fewer than 10 attempts per model is a canary, not strong evidence of long-term stability.');
        }

        $this->line('* configured application model');
    }

    /**
     * @param  array<int, array<string, int|float|string>>  $summaries
     */
    private function writeJsonReport(
        string $path,
        array $summaries,
        string $configuredModel,
        int $attempts,
        int $maxOutputTokens,
    ): void {
        $directory = dirname($path);
        if ($directory !== '.' && ! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode([
            'generated_at' => now()->toIso8601String(),
            'configured_model' => $configuredModel,
            'attempts_per_model' => $attempts,
            'max_output_tokens' => $maxOutputTokens,
            'prompt' => 'fixed_chemistry_water_formula_v1',
            'results' => $summaries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info("JSON report written to {$path}.");
    }

    /**
     * @param  array<int, string>  $models
     * @return array<int, string>
     */
    private function rotatedModels(array $models, int $round): array
    {
        $count = count($models);
        if ($count < 2) {
            return $models;
        }

        $offset = $round % $count;

        return array_merge(array_slice($models, $offset), array_slice($models, 0, $offset));
    }

    private function client(string $apiKey, int $timeout): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->connectTimeout(min(15, $timeout))
            ->timeout($timeout);
    }

    /**
     * @param  array<string, mixed>  $model
     */
    private function supportsGenerateContent(array $model): bool
    {
        return in_array('generateContent', (array) ($model['supportedGenerationMethods'] ?? []), true);
    }

    private function isTextBenchmarkCandidate(string $model, bool $includePreview): bool
    {
        if (! str_starts_with($model, 'gemini-')) {
            return false;
        }

        if (preg_match('/(embedding|image|imagen|veo|lyria|tts|live|audio|transcribe|computer-use|robotics|deep-research|nano-banana|omni)/i', $model)) {
            return false;
        }

        return $includePreview
            || ! preg_match('/(preview|experimental|exp-|latest)/i', $model);
    }

    private function modelCategory(string $model): string
    {
        if ($this->isTextBenchmarkCandidate($model, false)) {
            return 'stable text candidate';
        }

        if ($this->isTextBenchmarkCandidate($model, true)) {
            return 'preview/alias text candidate';
        }

        return 'specialized/non-text';
    }

    private function normalizeModelId(string $model): string
    {
        return preg_replace('#^models/#', '', trim($model)) ?? '';
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }

    /**
     * @param  array<int, float>  $values
     */
    private function percentile(array $values, float $percentile): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values, SORT_NUMERIC);
        $index = max(0, (int) ceil(count($values) * $percentile) - 1);

        return round($values[$index], 2);
    }

    private function milliseconds(int|float|string $value): string
    {
        return number_format((float) $value, 2).' ms';
    }

    private function safeExceptionMessage(Throwable $exception, string $apiKey): string
    {
        return str_replace($apiKey, '[REDACTED]', $exception->getMessage());
    }
}
