<?php

use App\Logger\Logger;

test('el contenedor resuelve Logger como singleton', function () {
    $first = app(Logger::class);
    $second = app(Logger::class);

    expect($first)->toBe($second);
});

test('el contenedor devuelve la misma instancia que getInstance', function () {
    $fromContainer = app(Logger::class);
    $fromSingleton = Logger::getInstance();

    expect($fromContainer)->toBe($fromSingleton);
});
