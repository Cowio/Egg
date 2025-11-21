<?php

namespace G4\Egg\Handlers;

use G4\Egg\Handlers\ProcessReport;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{
    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        // Dispatch async job with serializable payload so workers can process concurrently
        $payload = [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        ProcessReport::dispatch($payload);

        parent::report($e); // Call the parent report method to ensure default behavior
    }
}