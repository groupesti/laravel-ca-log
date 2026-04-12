<?php

declare(strict_types=1);

use CA\Log\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

Route::prefix('logs')->as('ca.logs.')->group(function (): void {
    Route::get('/', [LogController::class, 'index'])->name('index');
    Route::get('/stats', [LogController::class, 'stats'])->name('stats');
});
