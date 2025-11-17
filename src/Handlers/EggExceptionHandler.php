<?php

namespace G4\Egg\Handlers;

use G4\Egg\Models\CaughtException;
use Illuminate\Foundation\exceptions\Handler as ExceptionHandler;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{

    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        // Custom logic to log exception to database or external service can be added here
        $exception = CaughtException::fromException($e);
        $exception->category = 'general'; // You can categorize exceptions if needed
        $exception->hash = md5($e->getMessage() . $e->getFile() . $e->getLine());
        $exception->save();

        parent::report($e); // Call the parent report method to ensure default behavior
    }
}