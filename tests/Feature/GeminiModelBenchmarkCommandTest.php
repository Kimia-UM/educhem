<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiModelBenchmarkCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.providers.gemini.key', 'test-gemini-key');
        config()->set('ai.providers.gemini.url', 'https://generativelanguage.test/v1beta/');
        config()->set('ai.providers.gemini.models.text.default', 'gemini-fast');
    }

    public function test_it_lists_models_available_to_the_configured_key_without_generation(): void
    {
        Http::fake([
            'generativelanguage.test/v1beta/models*' => Http::response([
                'models' => [
                    $this->model('gemini-fast'),
                    $this->model('text-embedding-model', ['embedContent']),
                ],
            ]),
        ]);

        $this->artisan('gemini:benchmark-models', ['--list-only' => true])
            ->expectsOutputToContain('Gemini returned 2 accessible models')
            ->expectsOutputToContain('gemini-fast')
            ->expectsOutputToContain('text-embedding-model')
            ->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/v1beta/models')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key'));
    }

    public function test_it_benchmarks_requested_generation_models_without_storing_responses(): void
    {
        $reportPath = storage_path('framework/testing/gemini-benchmark.json');
        File::delete($reportPath);

        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'models' => [
                        $this->model('gemini-fast'),
                        $this->model('gemini-stable'),
                    ],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'H₂O']],
                    ],
                ]],
                'usageMetadata' => ['totalTokenCount' => 12],
            ]);
        });

        $this->artisan('gemini:benchmark-models', [
            '--models' => 'gemini-fast,gemini-stable',
            '--attempts' => 2,
            '--delay-ms' => 0,
            '--timeout' => 5,
            '--max-output-tokens' => 256,
            '--max-models' => 2,
            '--json' => $reportPath,
        ])
            ->expectsOutputToContain('2 models × 2 attempts (4 total billable requests)')
            ->expectsOutputToContain('gemini-fast')
            ->expectsOutputToContain('gemini-stable')
            ->assertSuccessful();

        $report = json_decode(File::get($reportPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $report['results']);
        $this->assertSame(2, $report['results'][0]['successes']);
        $this->assertSame(100, $report['results'][0]['success_rate']);
        $this->assertArrayNotHasKey('response', $report['results'][0]);
        File::delete($reportPath);

        Http::assertSentCount(5);
        Http::assertSent(function (Request $request): bool {
            if ($request->method() !== 'POST') {
                return false;
            }

            return data_get($request->data(), 'generationConfig.maxOutputTokens') === 256
                && data_get($request->data(), 'contents.0.parts.0.text') !== null;
        });
    }

    public function test_it_rejects_a_requested_model_that_is_not_available_to_the_key(): void
    {
        Http::fake([
            'generativelanguage.test/v1beta/models*' => Http::response([
                'models' => [$this->model('gemini-fast')],
            ]),
        ]);

        $this->artisan('gemini:benchmark-models', [
            '--models' => 'gemini-missing',
        ])
            ->expectsOutputToContain('Unavailable model(s) for this API key: gemini-missing')
            ->assertExitCode(2);

        Http::assertSentCount(1);
    }

    public function test_automatic_selection_excludes_media_models_that_support_generate_content(): void
    {
        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([
                    'models' => [
                        $this->model('gemini-3.8-flash'),
                        $this->model('gemini-nano-banana-2.1'),
                        $this->model('gemini-omni-1.1-flash'),
                    ],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'H2O']]],
                ]],
            ]);
        });

        $this->artisan('gemini:benchmark-models', [
            '--attempts' => 1,
            '--delay-ms' => 0,
            '--timeout' => 5,
        ])
            ->expectsOutputToContain('Benchmarking 1 models × 1 attempts (1 total billable requests)')
            ->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'nano-banana')
            || str_contains($request->url(), 'omni'));
    }

    /**
     * @param  array<int, string>  $methods
     * @return array<string, mixed>
     */
    private function model(string $id, array $methods = ['generateContent']): array
    {
        return [
            'name' => 'models/'.$id,
            'displayName' => $id,
            'inputTokenLimit' => 1_000_000,
            'outputTokenLimit' => 8_192,
            'supportedGenerationMethods' => $methods,
        ];
    }
}
