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
        'context',
        'category',
        'hash',
    ];

    protected $casts = [
        'context' => 'array', // Automatically decode JSON
    ];
}