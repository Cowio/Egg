<?php

namespace G4\Egg\Handlers;

use G4\Egg\Models\CaughtException;
use G4\Egg\Services\SlackNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

class ProcessReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Max attempts and backoff for transient AI/HTTP issues
    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    /**
     * Exception payload (serializable)
     * @var array{
     *   exception_class: string,
     *   message: string,
     *   file: string,
     *   line: int,
     *   trace: string
     * }
     */
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        // Use a dedicated queue to allow parallel workers without blocking others
        $this->onQueue('egg');
    }

    public function handle(): void
    {
        // Build model from provided serializable data
        $exception = new CaughtException();
        $exception->exception_class = $this->data['exception_class'] ?? 'Unknown';
        $exception->message = $this->data['message'] ?? '';
        $exception->file = $this->data['file'] ?? '';
        $exception->line = (int)($this->data['line'] ?? 0);
        $exception->trace = $this->data['trace'] ?? '';

        // Categorize using AI, but never fail the job because of the AI call
        $category = 'Internal';
        try {
            $provider = Provider::tryFrom(config('egg.ai_provider'));
            $model = config('egg.ai_model');
            if ($provider && $model) {
                $response = Prism::text()
                    ->using($provider, $model)
                    ->withPrompt(
                        'Respond with either External or Internal based on whether the following exception is caused by external factors (like user input, network issues, third-party services) or internal factors (like bugs in the code, server issues). Exception: '
                        . $exception->exception_class . ' — ' . $exception->message
                    )
                    ->asText();
                $text = trim((string)($response->text ?? ''));
                if ($text !== '') {
                    $category = $text;
                }
            }
        } catch (Throwable $_) {
            // Keep default category on AI failure
        }

        $exception->category = $category;
        $exception->hash = md5(($exception->message ?? '') . ($exception->file ?? '') . (string)($exception->line ?? ''));
        $exception->save();

        // Fire-and-forget Slack notification; ignore result
        SlackNotifier::send($exception);
    }

}