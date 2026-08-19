<?php

declare(strict_types=1);

namespace App\Http\Requests\App\ContentPlan;

use App\Enums\Ai\ContentStyle;
use App\Enums\PostPlatform\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateContentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'social_account_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:7'],
            'items.*.date' => ['required', 'date_format:Y-m-d'],
            'items.*.format' => [
                'required',
                'string',
                Rule::in([
                    ContentType::InstagramFeed->value,
                    ContentType::InstagramStory->value,
                    ContentType::CAROUSEL_FORMAT,
                ]),
            ],
            'items.*.prompt' => ['required', 'string', 'min:3', 'max:2000'],
            'items.*.image_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'items.*.template' => ['nullable', 'string', Rule::enum(ContentStyle::class)],
            'items.*.apply_brand_visuals' => ['nullable', 'boolean'],
        ];
    }
}
