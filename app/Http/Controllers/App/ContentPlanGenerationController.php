<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Enums\Ai\ContentStyle;
use App\Http\Requests\App\ContentPlan\GenerateContentPlanRequest;
use App\Jobs\Ai\StreamPostCreation;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ContentPlanGenerationController extends Controller
{
    public function store(GenerateContentPlanRequest $request): JsonResponse
    {
        $workspace = $request->user()->currentWorkspace;

        $this->authorize('createPost', $workspace);

        $gate = Gate::inspect('useAi', $workspace->account);
        if ($gate->denied()) {
            return response()->json(['message' => $gate->message()], Response::HTTP_PAYMENT_REQUIRED);
        }

        $socialAccount = SocialAccount::query()
            ->where('id', $request->string('social_account_id')->toString())
            ->where('workspace_id', $workspace->id)
            ->whereIn('platform', ['instagram', 'instagram-facebook'])
            ->where('active', true)
            ->first();

        if (! $socialAccount) {
            return response()->json([
                'message' => 'Выберите активный Instagram-аккаунт этого рабочего пространства.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reviewLabel = $workspace->labels()->firstOrCreate(
            ['name' => 'На согласовании'],
            ['color' => '#F59E0B'],
        );

        $queued = [];

        foreach ($request->validated('items') as $item) {
            $creationId = (string) Str::uuid();
            $format = (string) $item['format'];
            $imageCount = isset($item['image_count'])
                ? (int) $item['image_count']
                : ($format === 'instagram_carousel' ? 5 : 1);

            StreamPostCreation::dispatch(
                userId: $request->user()->id,
                creationId: $creationId,
                workspaceId: $workspace->id,
                format: $format,
                socialAccountId: $socialAccount->id,
                imageCount: $imageCount,
                prompt: (string) $item['prompt'],
                date: (string) $item['date'],
                template: (string) ($item['template'] ?? ContentStyle::default()->value),
                applyBrandVisuals: (bool) ($item['apply_brand_visuals'] ?? true),
                labelIds: [$reviewLabel->id],
            );

            $queued[] = [
                'creation_id' => $creationId,
                'date' => (string) $item['date'],
                'format' => $format,
            ];
        }

        return response()->json([
            'message' => 'Контент на неделю поставлен в генерацию и после готовности появится в разделе «На согласовании».',
            'count' => count($queued),
            'items' => $queued,
        ], Response::HTTP_ACCEPTED);
    }
}
