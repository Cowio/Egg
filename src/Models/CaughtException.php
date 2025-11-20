<?php

namespace G4\Egg\Models;

use Illuminate\Database\Eloquent\Model;

class CaughtException extends Model
{
    protected $table = 'egg_exceptions';

    protected $fillable = [
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'category',
        'hash',
    ];

    protected $casts = [
        'context' => 'array', // Automatically decode JSON
    ];

    public static function fromException(\Throwable $exception): self
    {
        $model = new self();
        $model->exception_class = get_class($exception);
        $model->message = $exception->getMessage();
        $model->file = $exception->getFile();
        $model->line = $exception->getLine();
        $model->trace = $exception->getTraceAsString();
        return $model;
    }
}