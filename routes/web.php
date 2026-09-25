<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WorkoutItemController;
use Illuminate\Support\Facades\Route;

Route::get('/enter', [PasswordController::class, 'create'])->name('enter');
Route::post('/enter', [PasswordController::class, 'store'])->middleware('throttle:8,1')->name('enter.store');

Route::get('/', fn () => redirect()->route('today'));

Route::get('/today', TodayController::class)->name('today');

Route::patch('/workout-items/{workoutItem}', [WorkoutItemController::class, 'update'])
    ->name('workout-items.update');

Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');

Route::get('/calendar/{date}', [CalendarController::class, 'show'])
    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
    ->name('calendar.show');

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf-token');

Route::get('/manifest.webmanifest', function () {
    $base = rtrim(request()->getBasePath(), '/');

    $manifest = [
        'name' => 'Morning Workout',
        'short_name' => 'Workout',
        'description' => 'A short daily morning workout tracker.',
        'id' => ($base === '' ? '/' : $base.'/'),
        'start_url' => $base.'/enter',
        'scope' => $base === '' ? '/' : $base.'/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#eef3fb',
        'theme_color' => '#1d4ed8',
        'icons' => [
            [
                'src' => $base.'/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $base.'/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $base.'/icons/icon-maskable-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ];

    return response(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('manifest');
