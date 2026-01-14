<?php

namespace G4\Egg\Handlers;

use G4\Egg\Jobs\SendEggReportJob;
use Illuminate\Foundation\exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Http;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{
    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        SendEggReportJob::dispatch([
            'message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        parent::report($e); // Call the parent report method to ensure default behavior
    }
}
