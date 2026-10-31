<?php

use App\Http\Controllers\Api\McpController;
use App\Http\Middleware\RequireMcpToken;
use Illuminate\Support\Facades\Route;

Route::middleware([RequireMcpToken::class, 'throttle:60,1'])
    ->prefix('mcp')
    ->group(function () {
        Route::get('day', [McpController::class, 'day'])->name('api.mcp.day');
        Route::get('summary', [McpController::class, 'summary'])->name('api.mcp.summary');
        Route::get('recent', [McpController::class, 'recent'])->name('api.mcp.recent');
    });
