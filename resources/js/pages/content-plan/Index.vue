<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    IconCalendarWeek,
    IconChevronRight,
    IconLayoutGrid,
    IconMovie,
    IconPhoto,
    IconRefresh,
    IconSparkles,
} from '@tabler/icons-vue';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

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
    },
    {
        offset: 1,
        type: 'Stories',
        format: 'instagram_story',
        icon: IconSparkles,
        angle: 'серия Stories: короткий полезный факт, вопрос аудитории и мягкий призыв узнать подробнее об обучении',
    },
    {
        offset: 2,
        type: 'Карусель',
        format: 'instagram_carousel',
        icon: IconLayoutGrid,
        angle: 'карусель на 5–7 карточек: распространённые ошибки, правильный подход и итоговый чек-лист',
    },
    {
        offset: 4,
        type: 'Пост',
        format: 'instagram_feed',
        icon: IconPhoto,
        angle: 'продающий пост без агрессивных продаж: покажи пользу курса, кому он подходит и какой практический результат получает слушатель',
    },
    {
        offset: 5,
        type: 'Reels-сценарий',
        format: 'instagram_feed',
        icon: IconMovie,
        angle: 'сценарий Reels на 25–35 секунд: сильный хук, 3 коротких тезиса, финальный CTA. Выдай текст для ведущего и подсказки по кадрам',
    },
    {
        offset: 3,
        type: 'Stories',
        format: 'instagram_story',
        icon: IconSparkles,
        angle: 'серия интерактивных Stories: мини-тест из 3 вопросов по теме с правильными ответами и объяснением',
    },
    {
        offset: 6,
        type: 'Карусель',
        format: 'instagram_carousel',
        icon: IconLayoutGrid,
        angle: 'карусель-FAQ: собери 5 частых вопросов руководителей и педагогов по теме и дай короткие понятные ответы',
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
                            Выберите направление и язык. Система подготовит недельную сетку, а каждый материал можно сразу отправить в AI-генератор.
                        </p>
                    </div>
                </div>

                <section class="grid gap-4 rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs lg:grid-cols-[1.5fr_.7fr_.7fr_auto] lg:items-end">
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
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold">План: {{ selectedTopic }}</h2>
                            <p class="text-sm text-muted-foreground">Каждый материал сначала создаётся как черновик — публикация автоматически не запускается.</p>
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
