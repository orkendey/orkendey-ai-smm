<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentApprovalController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        if (! $workspace) {
            return redirect()->route('app.workspaces.create');
        }

        $this->authorize('view', $workspace);

        $posts = $workspace->posts()
            ->with(['postPlatforms.socialAccount', 'labels'])
            ->whereHas('labels', fn ($query) => $query->where('name', 'На согласовании'))
            ->latest('scheduled_at')
            ->paginate(config('app.pagination.default'));

        return Inertia::render('content-approval/Index', [
            'posts' => $posts,
        ]);
    }

    public function approve(Request $request, Post $post): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('update', $post);

        abort_unless($post->workspace_id === $workspace->id, 404);

        $reviewLabel = $workspace->labels()->where('name', 'На согласовании')->first();
        $approvedLabel = $workspace->labels()->firstOrCreate(
            ['name' => 'Одобрено'],
            ['color' => '#22C55E'],
        );

        if ($reviewLabel) {
            $post->labels()->detach($reviewLabel->id);
        }
        $post->labels()->syncWithoutDetaching([$approvedLabel->id]);

        return back()->with('flash.banner', 'Материал одобрен. Он остаётся черновиком до отдельного планирования публикации.')
            ->with('flash.bannerStyle', 'success');
    }
}
