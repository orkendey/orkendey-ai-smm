<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\Post\Status as PostStatus;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function approved(Request $request): Response|RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;

        if (! $workspace) {
            return redirect()->route('app.workspaces.create');
        }

        $this->authorize('view', $workspace);

        $posts = $workspace->posts()
            ->with(['postPlatforms.socialAccount', 'labels'])
            ->where('status', PostStatus::Draft)
            ->whereHas('labels', fn ($query) => $query->where('name', 'Одобрено'))
            ->orderBy('scheduled_at')
            ->paginate(config('app.pagination.default'));

        return Inertia::render('content-approved/Index', [
            'posts' => $posts,
        ]);
    }

    public function approve(Request $request, Post $post): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('update', $post);

        abort_unless($post->workspace_id === $workspace->id, 404);

        $this->moveToApproved($workspace, $post);

        return back()->with('flash.banner', 'Материал одобрен. Он остаётся черновиком до отдельного планирования публикации.')
            ->with('flash.bannerStyle', 'success');
    }

    public function approveAll(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);

        $posts = $workspace->posts()
            ->where('status', PostStatus::Draft)
            ->whereHas('labels', fn ($query) => $query->where('name', 'На согласовании'))
            ->get();

        foreach ($posts as $post) {
            $this->moveToApproved($workspace, $post);
        }

        return redirect()->route('app.content-approved')
            ->with('flash.banner', "Одобрено материалов: {$posts->count()}.")
            ->with('flash.bannerStyle', 'success');
    }

    public function scheduleAll(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);

        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i'],
        ]);

        $approvedLabel = $workspace->labels()->where('name', 'Одобрено')->first();
        if (! $approvedLabel) {
            return back()->with('flash.banner', 'Нет одобренных материалов для планирования.')
                ->with('flash.bannerStyle', 'warning');
        }

        $posts = $workspace->posts()
            ->with(['postPlatforms'])
            ->where('status', PostStatus::Draft)
            ->whereHas('labels', fn ($query) => $query->whereKey($approvedLabel->id))
            ->orderBy('scheduled_at')
            ->get();

        $scheduled = 0;
        $skipped = 0;

        DB::transaction(function () use ($posts, $validated, $approvedLabel, &$scheduled, &$skipped): void {
            foreach ($posts as $post) {
                if (! $post->scheduled_at || ! $post->postPlatforms->contains('enabled', true)) {
                    $skipped++;
                    continue;
                }

                $date = $post->scheduled_at->setTimezone('Asia/Almaty')->format('Y-m-d');
                $scheduledAt = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    "{$date} {$validated['time']}",
                    'Asia/Almaty',
                )->utc();

                if ($scheduledAt->isPast()) {
                    $skipped++;
                    continue;
                }

                $post->update([
                    'status' => PostStatus::Scheduled,
                    'scheduled_at' => $scheduledAt,
                ]);
                $post->labels()->detach($approvedLabel->id);
                $scheduled++;
            }
        });

        $message = "Запланировано материалов: {$scheduled}.";
        if ($skipped > 0) {
            $message .= " Пропущено: {$skipped} (нет даты/аккаунта или дата уже прошла).";
        }

        return redirect()->route('app.calendar')
            ->with('flash.banner', $message)
            ->with('flash.bannerStyle', $scheduled > 0 ? 'success' : 'warning');
    }

    private function moveToApproved($workspace, Post $post): void
    {
        $reviewLabel = $workspace->labels()->where('name', 'На согласовании')->first();
        $approvedLabel = $workspace->labels()->firstOrCreate(
            ['name' => 'Одобрено'],
            ['color' => '#22C55E'],
        );

        if ($reviewLabel) {
            $post->labels()->detach($reviewLabel->id);
        }
        $post->labels()->syncWithoutDetaching([$approvedLabel->id]);
    }
}
