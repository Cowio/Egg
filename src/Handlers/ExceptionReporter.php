<?php

namespace G4\Egg\Handlers;

use G4\Egg\Models\CaughtException;
use G4\Egg\Services\SlackNotifier;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Illuminate\Support\Facades\Log;
use Throwable;
class ExceptionReporter
{
    public function __construct(private Throwable $e, private string $hash) {}

    public function __invoke(): void
    {
        Log::info('Async exception reporter started', ['hash' => $this->hash]);

        try {
        $exception = CaughtException::fromException($this->e);

        $response = Prism::text()
            ->using(Provider::TryFrom(config('egg.ai_provider')), config('egg.ai_model'))
            ->withPrompt('Respond with either External or Internal based on whether the following exception is caused by external factors (like user input, network issues, third-party services) or internal factors (like bugs in the code, server issues). Exception message: ' . $exception)
            ->asText();

        $exception->category = $response->text ?? 'Unknown';
        $exception->hash = md5($this->e->getMessage() . $this->e->getFile() . $this->e->getLine());
        $exception->save();

        SlackNotifier::send($exception);
        }
        catch(Throwable $inner)
        {
            Log::error('Async exception reporter failed', [
                'hash' => $this->hash,
                'error' => $inner->getMessage(),
            ]);
        }

        dump('Async handler finished', ['hash' => $exception->hash]);
    }
}