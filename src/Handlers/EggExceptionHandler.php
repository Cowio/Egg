<?php

namespace G4\Egg\Handlers;

use Illuminate\Foundation\exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Http;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{
    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        Http::withoutRedirecting()
            ->post('http://localhost:8080/api/exception', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

//        SendEggReportJob::dispatch([
//            'message' => $e->getMessage(),
//            'exception_class' => get_class($e),
//            'code' => $e->getCode(),
//            'file' => $e->getFile(),
//            'line' => $e->getLine(),
//            'trace' => $e->getTraceAsString(),
//        ]);
//
//        $response->wait();

//        $exception = CaughtException::fromException($e);
//        $response = Prism::text()
//            ->using(Provider::TryFrom(config('egg.ai_provider')), config("egg.ai_model"))
//            ->withPrompt("Respond with either External or Internal based on whether the following exception is caused by external factors (like user input, network issues, third-party services) or internal factors (like bugs in the code, server issues). Exception message: " . $exception)
//            ->asText();
//
//        $exception->category = $response->text; // You can categorize exceptions if needed
//        $exception->hash = md5($e->getMessage() . $e->getFile() . $e->getLine());
//        $exception->save();
//
//        SlackNotifier::send($exception);

        parent::report($e); // Call the parent report method to ensure default behavior
    }
}
