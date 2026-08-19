<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { IconArrowLeft, IconCheck, IconLayoutGrid, IconPhoto, IconSparkles } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import ContentStylePicker from '@/components/ai/ContentStylePicker.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { getPlatformLogo } from '@/composables/usePlatformLogo';
import { loading as loadingRoute } from '@/routes/app/posts/ai';
import { ContentType } from '@/types/content-type';

interface SocialAccount {
    id: string;
    platform: string;
    display_name: string;
    username: string;
    display_label: string;
    avatar_url: string | null;
}

interface AiTemplate {
    key: string;
    name: string;
    description: string;
    preview: string;
    needs_account: boolean;
    supported_formats: string[];
    applies_brand_visuals: boolean;
}

interface Props {
    socialAccounts: SocialAccount[];
    templates: AiTemplate[];
    date?: string | null;
    initialPrompt?: string;
    initialFormat?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    date: null,
    initialPrompt: '',
    initialFormat: null,
});

const emit = defineEmits<{
    'update:stepHeader': [{ title: string; description: string }];
    cancel: [];
}>();

const CAROUSEL_FORMAT = 'instagram_carousel' as const;
type InstagramFormat = typeof ContentType.InstagramFeed | typeof ContentType.InstagramStory | typeof CAROUSEL_FORMAT;

const formats: Array<{
    value: InstagramFormat;
    title: string;
    description: string;
    icon: typeof IconPhoto;
}> = [
    {
        value: ContentType.InstagramFeed,
        title: 'Пост',
        description: 'Один пост в ленту Instagram',
        icon: IconPhoto,
    },
    {
        value: CAROUSEL_FORMAT,
        title: 'Карусель',
        description: '2–10 карточек для раскрытия темы',
        icon: IconLayoutGrid,
    },
    {
        value: ContentType.InstagramStory,
        title: 'Stories',
        description: 'Вертикальный контент для Stories',
        icon: IconSparkles,
    },
];

const isInstagramFormat = (value: string | null | undefined): value is InstagramFormat =>
    value === ContentType.InstagramFeed || value === ContentType.InstagramStory || value === CAROUSEL_FORMAT;

const selectedFormat = ref<InstagramFormat | null>(isInstagramFormat(props.initialFormat) ? props.initialFormat : null);
const selectedStyle = ref('image_card');
const selectedAccountId = ref<string | null>(null);
const imageCount = ref(selectedFormat.value === CAROUSEL_FORMAT ? 5 : 1);
const promptText = ref(props.initialPrompt);
const useBrandColors = ref(true);
const submitting = ref(false);

watch(
    () => props.initialPrompt,
    (value) => {
        if (value && !promptText.value.trim()) promptText.value = value;
    },
);

watch(
    () => props.initialFormat,
    (value) => {
        if (isInstagramFormat(value) && !selectedFormat.value) {
            selectedFormat.value = value;
            imageCount.value = value === CAROUSEL_FORMAT ? 5 : 1;
        }
    },
);

const instagramAccounts = computed(() =>
    props.socialAccounts.filter((account) =>
        ['instagram', 'instagram-facebook'].includes(account.platform),
    ),
);

watch(
    instagramAccounts,
    (accounts) => {
        if (accounts.length === 1) selectedAccountId.value = accounts[0].id;
        if (accounts.length === 0) selectedAccountId.value = null;
    },
    { immediate: true },
);

const styleTemplates = computed(() =>
    props.templates.filter((template) => template.supported_formats.length === 0),
);

const formatBoundTemplate = computed(() =>
    selectedFormat.value
        ? props.templates.find((template) => template.supported_formats.includes(selectedFormat.value as string)) ?? null
        : null,
);

const resolvedTemplate = computed(() => formatBoundTemplate.value?.key ?? selectedStyle.value);

const resolvedTemplateRecord = computed(() =>
    props.templates.find((template) => template.key === resolvedTemplate.value) ?? null,
);

const submittedImageCount = computed(() => {
    if (selectedFormat.value === CAROUSEL_FORMAT) return imageCount.value;
    if (selectedFormat.value === ContentType.InstagramStory) return 1;
    return 1;
});

const promptLength = computed(() => [...promptText.value.trim()].length);
const canSubmit = computed(() =>
    selectedFormat.value !== null &&
    selectedAccountId.value !== null &&
    promptLength.value >= 3 &&
    promptLength.value <= 2000,
);

const selectFormat = (format: InstagramFormat) => {
    selectedFormat.value = format;
    imageCount.value = format === CAROUSEL_FORMAT ? 5 : 1;
};

emit('update:stepHeader', {
    title: 'AI SMM-агент',
    description: 'Выберите формат, проверьте подготовленный запрос и запустите генерацию.',
});

const startGeneration = () => {
    if (!canSubmit.value || submitting.value) return;

    submitting.value = true;

    router.visit(
        loadingRoute(
            { creationId: crypto.randomUUID() },
            {
                query: {
                    images: String(submittedImageCount.value),
                    format: selectedFormat.value ?? '',
                    prompt: promptText.value.trim(),
                    social_account_id: selectedAccountId.value ?? '',
                    date: props.date ?? '',
                    template: resolvedTemplate.value,
                    apply_brand_visuals: useBrandColors.value ? '1' : '0',
                },
            },
        ).url,
        {
            onError: () => toast.error('Не удалось запустить генерацию. Проверьте подключение AI-провайдера.'),
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="space-y-6">
        <button
            type="button"
            class="group inline-flex items-center gap-1.5 text-sm font-semibold text-foreground/70 transition-colors hover:text-foreground"
            @click="emit('cancel')"
        >
            <span class="inline-flex size-7 items-center justify-center rounded-md border-2 border-foreground bg-card shadow-2xs transition-transform group-hover:-translate-x-0.5">
                <IconArrowLeft class="size-3.5" stroke-width="2.5" />
            </span>
            Назад к направлениям
        </button>

        <div class="rounded-2xl border-2 border-foreground bg-violet-50 p-5 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border-2 border-foreground bg-violet-200">
                    <IconSparkles class="size-5" />
                </div>
                <div>
                    <p class="font-bold">Orkendey AI SMM</p>
                    <p class="mt-1 text-sm text-foreground/70">
                        Запрос уже подготовлен на основе выбранного направления. Его можно изменить перед генерацией.
                    </p>
                </div>
            </div>
        </div>

        <section class="space-y-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">Шаг 2</p>
                <Label class="text-base font-bold">Формат Instagram</Label>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <button
                    v-for="format in formats"
                    :key="format.value"
                    type="button"
                    class="flex items-start gap-3 rounded-xl border-2 border-foreground bg-card p-4 text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                    :class="{ '!bg-violet-100': selectedFormat === format.value }"
                    @click="selectFormat(format.value)"
                >
                    <component :is="format.icon" class="mt-0.5 size-5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold">{{ format.title }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ format.description }}</p>
                    </div>
                    <IconCheck v-if="selectedFormat === format.value" class="size-4 shrink-0" stroke-width="3" />
                </button>
            </div>
        </section>

        <section v-if="selectedFormat" class="space-y-4">
            <div v-if="instagramAccounts.length > 1" class="space-y-2">
                <Label class="font-bold">Instagram-аккаунт</Label>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="account in instagramAccounts"
                        :key="account.id"
                        type="button"
                        class="flex items-center gap-3 rounded-xl border-2 border-foreground bg-card p-3 text-left"
                        :class="{ '!bg-violet-100': selectedAccountId === account.id }"
                        @click="selectedAccountId = account.id"
                    >
                        <span class="inline-flex size-9 items-center justify-center overflow-hidden rounded-full border-2 border-foreground bg-card">
                            <img
                                v-if="account.avatar_url"
                                :src="account.avatar_url"
                                :alt="account.display_label"
                                class="size-full object-cover"
                            />
                            <img v-else :src="getPlatformLogo(account.platform)" :alt="account.platform" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ account.display_label }}</p>
                            <p v-if="account.username" class="truncate text-xs text-muted-foreground">@{{ account.username }}</p>
                        </div>
                        <IconCheck v-if="selectedAccountId === account.id" class="size-4" stroke-width="3" />
                    </button>
                </div>
            </div>

            <div v-if="selectedFormat === CAROUSEL_FORMAT" class="space-y-2">
                <Label class="font-bold">Количество карточек</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="count in [2, 3, 4, 5, 6, 7, 8, 9, 10]"
                        :key="count"
                        type="button"
                        size="icon"
                        :variant="imageCount === count ? 'default' : 'outline'"
                        @click="imageCount = count"
                    >
                        {{ count }}
                    </Button>
                </div>
            </div>

            <div v-if="!formatBoundTemplate && styleTemplates.length" class="space-y-2">
                <Label class="font-bold">Стиль визуала</Label>
                <ContentStylePicker v-model="selectedStyle" :styles="styleTemplates" />
            </div>

            <div
                v-if="resolvedTemplateRecord?.applies_brand_visuals"
                class="flex items-center justify-between gap-4 rounded-xl border bg-card p-4"
            >
                <div>
                    <p class="text-sm font-bold">Использовать фирменные цвета</p>
                    <p class="text-xs text-muted-foreground">AI будет ориентироваться на бренд рабочего пространства.</p>
                </div>
                <Switch v-model="useBrandColors" />
            </div>

            <div class="space-y-2">
                <Label for="ai-prompt" class="font-bold">Задача для AI</Label>
                <Textarea
                    id="ai-prompt"
                    v-model="promptText"
                    placeholder="Например: расскажи о новом курсе, добавь сильный заголовок и призыв оставить заявку"
                    class="min-h-[180px] resize-y"
                />
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Можно добавить цену, сроки, аудиторию и конкретный оффер.</span>
                    <span :class="{ 'font-bold text-destructive': promptLength > 2000 }">{{ promptLength }}/2000</span>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <Button size="lg" :disabled="!canSubmit || submitting" @click="startGeneration">
                    <IconSparkles class="mr-2 size-4" />
                    {{ submitting ? 'Запускаю…' : 'Создать с AI' }}
                </Button>
            </div>
        </section>
    </div>
</template>
