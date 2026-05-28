<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // ⚠️ TAMBAHAN: Wajib diimport untuk mendeteksi request API

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ─── AMANKAN ERROR API DI SINI ─────────────────────────────────────
        // Jika request datang dari Flutter/API (ditandai dengan url api/* atau meminta JSON),
        // Paksa Laravel untuk selalu mengembalikan JSON (bukan halaman HTML 500)
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
        // ─────────────────────────────────────────────────────────────────

    })->create();
