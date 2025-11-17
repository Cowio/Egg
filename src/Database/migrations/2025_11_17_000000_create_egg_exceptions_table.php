<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('egg_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('exception_class');
            $table->text('message');
            $table->string('file');
            $table->integer('line');
            $table->longText('trace');
            $table->string('category')->nullable();
            $table->string('hash')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('egg_exceptions');
    }
};

