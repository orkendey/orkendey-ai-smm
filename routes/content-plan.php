<?php

declare(strict_types=1);

use App\Http\Middleware\App\EnsureAccountReady;
use App\Http\Middleware\App\EnsureHasWorkspace;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', EnsureAccountReady::class, EnsureHasWorkspace::class])
    ->get('content-plan', fn () => Inertia::render('content-plan/Index'))
    ->name('app.content-plan');
