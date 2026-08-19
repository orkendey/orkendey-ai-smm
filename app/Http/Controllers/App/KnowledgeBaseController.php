<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Models\WorkspaceKnowledgeItem;
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

        $items = [
            ['category' => 'Компания', 'title' => 'О центре', 'content' => 'Учебно-методический центр «Өркендеу». Основная аудитория: школы, детские сады, колледжи, вузы, педагоги и руководители организаций образования Казахстана.'],
            ['category' => 'Контакты', 'title' => 'Сайты и контакты', 'content' => 'Сайты: orkendey.kz и orkendey.edu.kz. LMS: lms.orkendey.kz. E-mail: edu@orkendey.kz. Контакт: Дидара Вахитовна, +7 708 806 88 44.'],
            ['category' => 'Курсы', 'title' => 'Основные направления', 'content' => 'Направления обучения и материалов: безопасность и охрана труда (БиОТ), пожарная безопасность/ПТМ, антитеррористическая подготовка, профилактика буллинга, инклюзивное образование, согласительная комиссия, цифровая грамотность и ИИ, антикоррупционная тематика, санитарный минимум, профилактические и методические программы для организаций образования.'],
            ['category' => 'Продукты', 'title' => 'Лицензирование детских садов', 'content' => 'Өркендеу предлагает вспомогательный архив образцов, шаблонов и методических материалов для подготовки детских садов к лицензированию образовательной деятельности. Материалы необходимо адаптировать под конкретную организацию и актуализировать перед подачей. Архив не гарантирует получение лицензии.'],
            ['category' => 'Стиль', 'title' => 'Правила контента', 'content' => 'Тон: профессиональный, понятный, живой и доверительный. Не придумывать номера приказов, обязательные требования, цены, гарантии и юридические утверждения без подтверждённых исходных данных. Контент готовится на русском или грамотном казахском языке в зависимости от выбранного языка. Продажи мягкие, через пользу и экспертность.'],
        ];

        foreach ($items as $item) {
            $workspace->knowledgeItems()->updateOrCreate(
                ['title' => $item['title']],
                [...$item, 'is_active' => true],
            );
        }

        return back()->with('flash.banner', 'Базовые данные Өркендеу добавлены в базу знаний.')
            ->with('flash.bannerStyle', 'success');
    }
}
