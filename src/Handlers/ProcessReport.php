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

    public function handleReport(Throwable $e): void
    {
        // Custom logic to log exception to database or external service can be added here
        $exception = CaughtException::fromException($e);

        $response = Prism::text()
            ->using(Provider::TryFrom(config('egg.ai_provider')), config("egg.ai_model"))
            ->withPrompt("Respond with either External or Internal based on whether the following exception is caused by external factors (like user input, network issues, third-party services) or internal factors (like bugs in the code, server issues). Exception message: " . $exception)
            ->asText();

        $exception->category = $response->text; // You can categorize exceptions if needed
        $exception->hash = md5($e->getMessage() . $e->getFile() . $e->getLine());
        $exception->save();

        SlackNotifier::send($exception);

    }

}