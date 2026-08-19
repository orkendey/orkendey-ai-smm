<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    IconCalendarWeek,
    IconCheck,
    IconChevronRight,
    IconLayoutGrid,
    IconMovie,
    IconPhoto,
    IconRefresh,
    IconSparkles,
} from '@tabler/icons-vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface SocialAccount {
    id: string;
    platform: string;
    display_name: string;
    username: string;
    display_label: string;
    avatar_url: string | null;
}

interface Props {
    socialAccounts: SocialAccount[];
}

const props = defineProps<Props>();

const topics = [
    'Повышение квалификации',
    'Инклюзивное образование',
    'Буллинг',
    'БиОТ',
    'Антитеррор',
    'ПТМ',
    'Лицензирование ДОУ',
];

const selectedTopic = ref(topics[0]);
const language = ref<'ru' | 'kk'>('ru');
const intensity = ref<5 | 7>(5);
const generated = ref(false);
const submittingWeek = ref(false);
const queuedCount = ref(0);

const instagramAccounts = computed(() =>
    props.socialAccounts.filter((account) => ['instagram', 'instagram-facebook'].includes(account.platform)),
);

const selectedAccountId = ref<string | null>(instagramAccounts.value.length === 1 ? instagramAccounts.value[0].id : null);

watch(instagramAccounts, (accounts) => {
    if (accounts.length === 1) selectedAccountId.value = accounts[0].id;
    if (accounts.length === 0) selectedAccountId.value = null;
    if (selectedAccountId.value && !accounts.some((account) => account.id === selectedAccountId.value)) {
        selectedAccountId.value = null;
    }
});

const pad = (value: number) => String(value).padStart(2, '0');
const isoDate = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const monday = computed(() => {
    const now = new Date();
    const day = now.getDay() || 7;
    const result = new Date(now);
    result.setHours(12, 0, 0, 0);
    result.setDate(now.getDate() - day + 1);
    return result;
});

const contentPatterns = [
    {
        offset: 0,
        type: 'Пост',
        format: 'instagram_feed',
        icon: IconPhoto,
        angle: 'экспертный пост: объясни проблему, почему она важна для организации и дай 3 практических рекомендации',
        imageCount: 1,
    },
    {
        offset: 1,
        type: 'Stories',
        format: 'instagram_story',
        icon: IconSparkles,
        angle: 'серия Stories: короткий полезный факт, вопрос аудитории и мягкий призыв узнать подробнее об обучении',
        imageCount: 1,
    },
    {
        offset: 2,
        type: 'Карусель',
        format: 'instagram_carousel',
        icon: IconLayoutGrid,
        angle: 'карусель на 5–7 карточек: распространённые ошибки, правильный подход и итоговый чек-лист',
        imageCount: 6,
    },
    {
        offset: 4,
        type: 'Пост',
        format: 'instagram_feed',
        icon: IconPhoto,
        angle: 'продающий пост без агрессивных продаж: покажи пользу курса, кому он подходит и какой практический результат получает слушатель',
        imageCount: 1,
    },
    {
        offset: 5,
        type: 'Reels-сценарий',
        format: 'instagram_feed',
        icon: IconMovie,
        angle: 'сценарий Reels на 25–35 секунд: сильный хук, 3 коротких тезиса, финальный CTA. Выдай текст для ведущего и подсказки по кадрам',
        imageCount: 1,
    },
    {
        offset: 3,
        type: 'Stories',
        format: 'instagram_story',
        icon: IconSparkles,
        angle: 'серия интерактивных Stories: мини-тест из 3 вопросов по теме с правильными ответами и объяснением',
        imageCount: 1,
    },
    {
        offset: 6,
        type: 'Карусель',
        format: 'instagram_carousel',
        icon: IconLayoutGrid,
        angle: 'карусель-FAQ: собери 5 частых вопросов руководителей и педагогов по теме и дай короткие понятные ответы',
        imageCount: 5,
    },
];

const plan = computed(() => {
    const count = intensity.value;
    return contentPatterns.slice(0, count).map((item, index) => {
        const date = new Date(monday.value);
        date.setDate(date.getDate() + item.offset);
        const langInstruction = language.value === 'kk'
            ? 'Напиши весь итоговый контент на грамотном казахском языке.'
            : 'Напиши весь итоговый контент на русском языке.';
        const prompt = `Ты SMM-специалист учебно-методического центра «Өркендеу». Тема: ${selectedTopic.value}. Подготовь ${item.angle}. Аудитория: руководители и сотрудники организаций образования Казахстана. Тон профессиональный, живой и понятный. Не придумывай нормативные требования, номера приказов, цены или гарантии, если их нет в исходных данных. ${langInstruction}`;
        return {
            ...item,
            id: `${item.offset}-${index}`,
            date: isoDate(date),
            dateLabel: date.toLocaleDateString(language.value === 'kk' ? 'kk-KZ' : 'ru-RU', {
                weekday: 'long',
                day: 'numeric',
                month: 'short',
            }),
            prompt,
        };
    }).sort((a, b) => a.date.localeCompare(b.date));
});

const createHref = (item: (typeof plan.value)[number]) => {
    const params = new URLSearchParams({
        date: item.date,
        prompt: item.prompt,
        format: item.format,
        from: 'content-plan',
    });
    return `/posts/create?${params.toString()}`;
};

const generatePlan = () => {
    generated.value = true;
    queuedCount.value = 0;
};

const generateWholeWeek = async () => {
    if (!selectedAccountId.value || submittingWeek.value || !generated.value) return;

    submittingWeek.value = true;
    queuedCount.value = 0;

    try {
        const response = await axios.post('/content-plan/generate-week', {
            social_account_id: selectedAccountId.value,
            items: plan.value.map((item) => ({
                date: item.date,
                format: item.format,
                prompt: item.prompt,
                image_count: item.imageCount,
                apply_brand_visuals: true,
            })),
        });

        queuedCount.value = response.data.count ?? plan.value.length;
        toast.success(`${queuedCount.value} материалов поставлено в AI-генерацию`);
    } catch (error: any) {
        const message = error?.response?.data?.message ?? 'Не удалось запустить генерацию всей недели.';
        toast.error(message);
    } finally {
        submittingWeek.value = false;
    }
};
</script>

<template>
    <Head title="Контент-план" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4 md:p-6">
            <div class="mx-auto w-full max-w-6xl space-y-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full border bg-violet-50 px-3 py-1 text-xs font-bold">
                            <IconCalendarWeek class="size-4" />
                            AI SMM
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">Контент-план на неделю</h1>
                        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                            Выберите направление и язык. Можно создавать материалы по одному или поставить всю неделю в AI-генерацию одной кнопкой.
                        </p>
                    </div>
                </div>

                <section class="grid gap-4 rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs lg:grid-cols-[1.4fr_.7fr_.7fr_auto] lg:items-end">
                    <label class="space-y-2">
                        <span class="text-sm font-bold">Направление</span>
                        <select v-model="selectedTopic" class="h-10 w-full rounded-md border-2 border-foreground bg-background px-3 text-sm">
                            <option v-for="topic in topics" :key="topic" :value="topic">{{ topic }}</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-bold">Язык</span>
                        <select v-model="language" class="h-10 w-full rounded-md border-2 border-foreground bg-background px-3 text-sm">
                            <option value="ru">Русский</option>
                            <option value="kk">Қазақша</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <span class="text-sm font-bold">Материалов</span>
                        <select v-model="intensity" class="h-10 w-full rounded-md border-2 border-foreground bg-background px-3 text-sm">
                            <option :value="5">5 в неделю</option>
                            <option :value="7">7 в неделю</option>
                        </select>
                    </label>

                    <Button class="gap-2" @click="generatePlan">
                        <IconSparkles v-if="!generated" class="size-4" />
                        <IconRefresh v-else class="size-4" />
                        {{ generated ? 'Обновить план' : 'Сформировать план' }}
                    </Button>
                </section>

                <section v-if="generated" class="space-y-4">
                    <div class="grid gap-4 rounded-2xl border bg-muted/30 p-5 md:grid-cols-[1fr_auto] md:items-end">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold">Instagram-аккаунт для генерации</label>
                            <select
                                v-model="selectedAccountId"
                                class="h-10 w-full max-w-xl rounded-md border-2 border-foreground bg-background px-3 text-sm"
                                :disabled="instagramAccounts.length === 0"
                            >
                                <option :value="null" disabled>
                                    {{ instagramAccounts.length ? 'Выберите Instagram-аккаунт' : 'Instagram не подключён' }}
                                </option>
                                <option v-for="account in instagramAccounts" :key="account.id" :value="account.id">
                                    {{ account.display_label }}{{ account.username ? ` (@${account.username})` : '' }}
                                </option>
                            </select>
                            <p class="text-xs text-muted-foreground">
                                Все материалы создаются как черновики. Даты используются для календаря, но автоматическая публикация не запускается.
                            </p>
                        </div>

                        <Button
                            size="lg"
                            class="gap-2"
                            :disabled="!selectedAccountId || submittingWeek || instagramAccounts.length === 0"
                            @click="generateWholeWeek"
                        >
                            <IconCheck v-if="queuedCount > 0" class="size-4" />
                            <IconSparkles v-else class="size-4" />
                            {{ submittingWeek ? 'Ставлю в очередь…' : queuedCount > 0 ? `В очереди: ${queuedCount}` : 'Создать всю неделю' }}
                        </Button>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold">План: {{ selectedTopic }}</h2>
                            <p class="text-sm text-muted-foreground">Можно запустить всю неделю сразу или открыть конкретный материал для ручной проверки prompt.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <article
                            v-for="item in plan"
                            :key="item.id"
                            class="group rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="flex items-start gap-4">
                                <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border-2 border-foreground bg-violet-100">
                                    <component :is="item.icon" class="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-foreground px-2.5 py-1 text-[11px] font-bold text-background">{{ item.type }}</span>
                                        <span class="text-xs font-semibold capitalize text-muted-foreground">{{ item.dateLabel }}</span>
                                    </div>
                                    <p class="mt-3 text-sm leading-relaxed">{{ item.angle }}</p>
                                </div>
                            </div>

                            <div class="mt-5 flex justify-end">
                                <Link
                                    :href="createHref(item)"
                                    class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90"
                                >
                                    Создать через AI
                                    <IconChevronRight class="size-4" />
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-2xl border border-dashed p-10 text-center">
                    <IconCalendarWeek class="mx-auto size-10 text-muted-foreground" />
                    <h3 class="mt-3 font-bold">План ещё не сформирован</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Выберите параметры выше и нажмите «Сформировать план».</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
