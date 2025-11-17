<?php

namespace G4\Egg\Handlers;

use Illuminate\Foundation\exceptions\Handler as ExceptionHandler;
use Throwable;

class EggExceptionHandler extends ExceptionHandler
{

    // Override report method to custom reporting of the exception
    public function report(Throwable $e): void
    {
        dump("Custom exception: " . $e->getMessage());
        parent::report($e); // Call the parent report method to ensure default behavior
    }
}