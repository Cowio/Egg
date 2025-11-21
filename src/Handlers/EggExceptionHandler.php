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
        dump("beginning exception report handling");
        dispatch(new processReport())->handleReport($e);

        parent::report($e); // Call the parent report method to ensure default behavior
    }
}