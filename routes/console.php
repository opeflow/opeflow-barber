<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspirar', function () {
    $this->comment(Inspiring::quote());
})->purpose('Exibe uma frase inspiradora');

Schedule::command('beneficios:expirar')->hourly()->withoutOverlapping();
