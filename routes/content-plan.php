<?php

declare(strict_types=1);

use App\Http\Controllers\App\ContentApprovalController;
use App\Http\Controllers\App\ContentPlanGenerationController;
use App\Http\Controllers\App\KnowledgeBaseController;
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

    Route::get('content-approval', [ContentApprovalController::class, 'index'])
        ->name('app.content-approval');
    Route::post('content-approval/approve-all', [ContentApprovalController::class, 'approveAll'])
        ->name('app.content-approval.approve-all');
    Route::post('content-approval/{post}/approve', [ContentApprovalController::class, 'approve'])
        ->name('app.content-approval.approve');

    Route::get('content-approved', [ContentApprovalController::class, 'approved'])
        ->name('app.content-approved');
    Route::post('content-approved/schedule-all', [ContentApprovalController::class, 'scheduleAll'])
        ->name('app.content-approved.schedule-all');

    Route::get('knowledge-base', [KnowledgeBaseController::class, 'index'])
        ->name('app.knowledge-base');
    Route::post('knowledge-base', [KnowledgeBaseController::class, 'store'])
        ->name('app.knowledge-base.store');
    Route::post('knowledge-base/seed-orkendey', [KnowledgeBaseController::class, 'seedOrkendey'])
        ->name('app.knowledge-base.seed-orkendey');
    Route::put('knowledge-base/{item}', [KnowledgeBaseController::class, 'update'])
        ->name('app.knowledge-base.update');
    Route::delete('knowledge-base/{item}', [KnowledgeBaseController::class, 'destroy'])
        ->name('app.knowledge-base.destroy');
});
