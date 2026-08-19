<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Models\WorkspaceKnowledgeItem;
use App\Services\Content\OrkendeyKnowledgeDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        if (! $workspace) {
            return redirect()->route('app.workspaces.create');
        }

        $this->authorize('createPost', $workspace);

        if (! $workspace->knowledgeItems()->exists()) {
            OrkendeyKnowledgeDefaults::ensure($workspace);
        }

        return Inertia::render('knowledge-base/Index', [
            'items' => $workspace->knowledgeItems()->orderBy('category')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:10000'],
            'is_active' => ['boolean'],
        ]);

        $workspace->knowledgeItems()->create($data);

        return back()->with('flash.banner', 'Запись добавлена в базу знаний.')
            ->with('flash.bannerStyle', 'success');
    }

    public function update(Request $request, WorkspaceKnowledgeItem $item): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);
        abort_unless($item->workspace_id === $workspace->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:10000'],
            'is_active' => ['boolean'],
        ]);

        $item->update($data);

        return back()->with('flash.banner', 'База знаний обновлена.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroy(Request $request, WorkspaceKnowledgeItem $item): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);
        abort_unless($item->workspace_id === $workspace->id, 404);

        $item->delete();

        return back()->with('flash.banner', 'Запись удалена.')
            ->with('flash.bannerStyle', 'success');
    }

    public function seedOrkendey(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        $this->authorize('createPost', $workspace);

        OrkendeyKnowledgeDefaults::ensure($workspace);

        return back()->with('flash.banner', 'Базовые данные Өркендеу добавлены в базу знаний.')
            ->with('flash.bannerStyle', 'success');
    }
}
