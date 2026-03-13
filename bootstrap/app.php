<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'studio-engine/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('studio-engine/*')) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;
            $message = trim((string) $e->getMessage());
            $reasonCode = 'server_error';
            $detail = $status >= 500
                ? 'The server could not finish this studio request.'
                : ($message !== '' ? $message : 'The studio request could not be completed.');

            if (str_contains($message, 'Malformed UTF-8')) {
                $reasonCode = 'invalid_metadata_encoding';
                $detail = 'One of the uploaded files contains metadata or text that is not valid UTF-8.';
            } elseif (str_contains(strtolower($message), 'zip') || str_contains(strtolower($message), 'extract')) {
                $reasonCode = 'archive_extract_failed';
                $detail = 'The package could not be unpacked. The archive may be corrupted or use an unsupported format.';
            } elseif (str_contains(strtolower($message), 'file not found') || str_contains(strtolower($message), 'no such file')) {
                $reasonCode = 'missing_source_file';
                $detail = 'A required source file was missing while the studio job was running.';
            } elseif ($status >= 500 && $message !== '') {
                $detail = $message;
            }

            return response()->json([
                'error' => $detail,
                'detail' => $detail,
                'reason_code' => $reasonCode,
                'status_code' => $status,
            ], $status, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        });
    })->create();
