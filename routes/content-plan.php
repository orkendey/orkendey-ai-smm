<?php

declare(strict_types=1);

use App\Http\Controllers\App\ContentPlanGenerationController;
use App\Http\Middleware\App\EnsureAccountReady;
use App\Http\Middleware\App\EnsureHasWorkspace;
use App\Http\Resources\App\SocialAccountResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', EnsureAccountReady::class, EnsureHasWorkspace::class])->group(function () {
    Route::get('content-plan', function (Request $request) {
        $workspace = $request->user()->currentWorkspace;

        return Inertia::render('content-plan/Index', [
            'socialAccounts' => SocialAccountResource::collection(
                $workspace->socialAccounts()->active()->get()
            ),
        ]);
    })->name('app.content-plan');

    Route::post('content-plan/generate-week', [ContentPlanGenerationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('app.content-plan.generate-week');
});
