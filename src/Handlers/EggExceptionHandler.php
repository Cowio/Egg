<?php

namespace G4\Egg\Handlers;

use G4\Egg\Models\CaughtException;
use G4\Egg\Services\SlackNotifier;
use Illuminate\Foundation\exceptions\Handler as ExceptionHandler;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{

    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        // Custom logic to log exception to database or external service can be added here
        $exception = CaughtException::fromException($e);

        $response = Prism::text()
            ->using(Provider::Anthropic, "claude-3-5-sonnet-20241022")
            ->withPrompt("Respond with either External or Internal based on whether the following exception is caused by external factors (like user input, network issues, third-party services) or internal factors (like bugs in the code, server issues). Exception message: " . $exception)
            ->asText();

        $exception->category = $response->text; // You can categorize exceptions if needed
        $exception->hash = md5($e->getMessage() . $e->getFile() . $e->getLine());
        $exception->save();

        SlackNotifier::send($exception);

        parent::report($e); // Call the parent report method to ensure default behavior
    }
}