<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (et tout reverse-proxy) termine le HTTPS : on fait confiance aux en-têtes X-Forwarded-*
        // pour que url(), asset() et les redirections génèrent bien des liens en https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'pro' => \App\Http\Middleware\ProMiddleware::class,
            'producer' => \App\Http\Middleware\ProducerMiddleware::class,
            'processor' => \App\Http\Middleware\ProcessorMiddleware::class,
            'consumer' => \App\Http\Middleware\ConsumerMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
