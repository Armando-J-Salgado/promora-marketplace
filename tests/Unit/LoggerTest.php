<?php

use App\Logger\Logger;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

test('getInstance retorna una instancia de Logger', function () {
    $logger = Logger::getInstance();

    expect($logger)->toBeInstanceOf(Logger::class);
});

test('getInstance siempre retorna la misma instancia', function () {
    $first = Logger::getInstance();
    $second = Logger::getInstance();

    expect($first)->toBe($second);
});

test('el constructor no es accesible publicamente', function () {
    $reflection = new ReflectionClass(Logger::class);
    $constructor = $reflection->getConstructor();

    expect($constructor->isPrivate())->toBeTrue();
});

test('log acumula mensajes en el array', function () {
    $logger = Logger::getInstance();
    $logger->log('primer mensaje');

    $reflection = new ReflectionClass($logger);
    $logs = $reflection->getProperty('logs');
    $logs->setAccessible(true);

    expect($logs->getValue($logger))->toBe(['primer mensaje']);
});

test('log acumula multiples mensajes en orden cronologico', function () {
    $logger = Logger::getInstance();
    $logger->log('primero');
    $logger->log('segundo');
    $logger->log('tercero');

    $reflection = new ReflectionClass($logger);
    $logs = $reflection->getProperty('logs');
    $logs->setAccessible(true);

    expect($logs->getValue($logger))->toBe(['primero', 'segundo', 'tercero']);
});
