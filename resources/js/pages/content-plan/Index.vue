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

const props = defineProps<{ socialAccounts: SocialAccount[] }>();
const topics = ['Повышение квалификации', 'Инклюзивное образование', 'Буллинг', 'БиОТ', 'Антитеррор', 'ПТМ', 'Лицензирование ДОУ'];
const selectedTopic = ref(topics[0]);
const language = ref<'ru' | 'kk'>('ru');
const intensity = ref<5 | 7>(5);
const generated = ref(false);
const submittingWeek = ref(false);
const queuedCount = ref(0);

const pad = (value: number) => String(value).padStart(2, '0');
const isoDate = (date: Date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const nextMonday = () => {
    const now = new Date();
    now.setHours(12, 0, 0, 0);
    const day = now.getDay() || 7;
    const daysUntilNextMonday = 8 - day;
    const result = new Date(now);
    result.setDate(now.getDate() + daysUntilNextMonday);
    return result;
};

const weekStart = ref(isoDate(nextMonday()));
const monday = computed(() => {
    const [year, month, day] = weekStart.value.split('-').map(Number);
    return new Date(year, month - 1, day, 12, 0, 0, 0);
});

const instagramAccounts = computed(() =>
    props.socialAccounts.filter((account) => ['instagram', 'instagram-facebook'].includes(account.platform)),
);
const selectedAccountId = ref<string | null>(instagramAccounts.value.length === 1 ? instagramAccounts.value[0].id : null);

watch(instagramAccounts, (accounts) => {
    if (accounts.length === 1) selectedAccountId.value = accounts[0].id;
    if (!accounts.some((account) => account.id === selectedAccountId.value)) selectedAccountId.value = null;
});

const patterns = [
    { offset: 0, type: 'Пост', format: 'instagram_feed', icon: IconPhoto, imageCount: 1, angle: 'экспертный пост: объясни проблему, почему она важна для организации и дай 3 практических рекомендации' },
    { offset: 1, type: 'Stories', format: 'instagram_story', icon: IconSparkles, imageCount: 1, angle: 'серия Stories: полезный факт, вопрос аудитории и мягкий призыв узнать подробнее' },
    { offset: 2, type: 'Карусель', format: 'instagram_carousel', icon: IconLayoutGrid, imageCount: 6, angle: 'карусель на 5–7 карточек: распространённые ошибки, правильный подход и итоговый чек-лист' },
    { offset: 4, type: 'Пост', format: 'instagram_feed', icon: IconPhoto, imageCount: 1, angle: 'мягко продающий пост: польза курса, кому подходит и какой практический результат получает слушатель' },
    { offset: 5, type: 'Reels-сценарий', format: 'instagram_feed', icon: IconMovie, imageCount: 1, angle: 'сценарий Reels на 25–35 секунд: хук, 3 тезиса, финальный CTA, текст ведущего и подсказки по кадрам' },
    { offset: 3, type: 'Stories', format: 'instagram_story', icon: IconSparkles, imageCount: 1, angle: 'серия интерактивных Stories: мини-тест из 3 вопросов с правильными ответами и объяснением' },
    { offset: 6, type: 'Карусель', format: 'instagram_carousel', icon: IconLayoutGrid, imageCount: 5, angle: 'карусель-FAQ: 5 частых вопросов руководителей и педагогов и короткие понятные ответы' },
];

const plan = computed(() => patterns.slice(0, intensity.value).map((item, index) => {
    const date = new Date(monday.value);
    date.setDate(date.getDate() + item.offset);
    const langInstruction = language.value === 'kk'
        ? 'Напиши весь итоговый контент на грамотном казахском языке.'
        : 'Напиши весь итоговый контент на русском языке.';
    const prompt = `Ты SMM-специалист учебно-методического центра «Өркендеу». Тема: ${selectedTopic.value}. Подготовь ${item.angle}. Аудитория: руководители и сотрудники организаций образования Казахстана. Используй проверенные данные из базы знаний рабочего пространства. Не придумывай нормативные требования, номера приказов, цены или гарантии. Тон профессиональный, живой и понятный. ${langInstruction}`;

    return {
        ...item,
        id: `${item.offset}-${index}`,
        date: isoDate(date),
        dateLabel: date.toLocaleDateString(language.value === 'kk' ? 'kk-KZ' : 'ru-RU', { weekday: 'long', day: 'numeric', month: 'short' }),
        prompt,
    };
}).sort((a, b) => a.date.localeCompare(b.date)));

const createHref = (item: (typeof plan.value)[number]) => {
    const params = new URLSearchParams({ date: item.date, prompt: item.prompt, format: item.format, from: 'content-plan' });
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
        toast.error(error?.response?.data?.message ?? 'Не удалось запустить генерацию всей недели.');
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
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full border bg-violet-50 px-3 py-1 text-xs font-bold">
                        <IconCalendarWeek class="size-4" /> AI SMM
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight">Контент-план на неделю</h1>
                    <p class="mt-2 max-w-2xl text-sm text-muted-foreground">Выберите неделю, направление и язык. По умолчанию ставится ближайший понедельник, поэтому публикации не попадают в прошлое.</p>
                </div>

                <section class="grid gap-4 rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs lg:grid-cols-5 lg:items-end">
                    <label class="space-y-2 lg:col-span-2">
                        <span class="text-sm font-bold">Направление</span>
                        <select v-model="selectedTopic" class="h-10 w-full rounded-md border-2 border-foreground bg-background px-3 text-sm">
                            <option v-for="topic in topics" :key="topic" :value="topic">{{ topic }}</option>
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-bold">Неделя с</span>
                        <input v-model="weekStart" type="date" class="h-10 w-full rounded-md border-2 border-foreground bg-background px-3 text-sm" />
                    </label>
                    <label class="space-y-2">
                        <span class="text-sm font-bold">Язык / объём</span>
                        <div class="grid grid-cols-2 gap-2">
                            <select v-model="language" class="h-10 rounded-md border-2 border-foreground bg-background px-2 text-sm"><option value="ru">RU</option><option value="kk">KZ</option></select>
                            <select v-model="intensity" class="h-10 rounded-md border-2 border-foreground bg-background px-2 text-sm"><option :value="5">5</option><option :value="7">7</option></select>
                        </div>
                    </label>
                    <Button class="gap-2" @click="generatePlan"><IconRefresh v-if="generated" class="size-4" /><IconSparkles v-else class="size-4" />{{ generated ? 'Обновить' : 'Сформировать' }}</Button>
                </section>

                <section v-if="generated" class="space-y-4">
                    <div class="grid gap-4 rounded-2xl border bg-muted/30 p-5 md:grid-cols-[1fr_auto] md:items-end">
                        <label class="space-y-2">
                            <span class="block text-sm font-bold">Instagram-аккаунт</span>
                            <select v-model="selectedAccountId" class="h-10 w-full max-w-xl rounded-md border-2 border-foreground bg-background px-3 text-sm" :disabled="!instagramAccounts.length">
                                <option :value="null" disabled>{{ instagramAccounts.length ? 'Выберите Instagram' : 'Instagram не подключён' }}</option>
                                <option v-for="account in instagramAccounts" :key="account.id" :value="account.id">{{ account.display_label }}{{ account.username ? ` (@${account.username})` : '' }}</option>
                            </select>
                        </label>
                        <Button size="lg" class="gap-2" :disabled="!selectedAccountId || submittingWeek" @click="generateWholeWeek">
                            <IconCheck v-if="queuedCount" class="size-4" /><IconSparkles v-else class="size-4" />
                            {{ submittingWeek ? 'Ставлю в очередь…' : queuedCount ? `В очереди: ${queuedCount}` : 'Создать всю неделю' }}
                        </Button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <article v-for="item in plan" :key="item.id" class="rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs">
                            <div class="flex items-start gap-4">
                                <div class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border-2 border-foreground bg-violet-100"><component :is="item.icon" class="size-5" /></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-foreground px-2.5 py-1 text-[11px] font-bold text-background">{{ item.type }}</span><span class="text-xs font-semibold capitalize text-muted-foreground">{{ item.dateLabel }}</span></div>
                                    <p class="mt-3 text-sm leading-relaxed">{{ item.angle }}</p>
                                </div>
                            </div>
                            <div class="mt-5 flex justify-end"><Link :href="createHref(item)" class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground">Создать через AI <IconChevronRight class="size-4" /></Link></div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-2xl border border-dashed p-10 text-center"><IconCalendarWeek class="mx-auto size-10 text-muted-foreground" /><h3 class="mt-3 font-bold">План ещё не сформирован</h3><p class="mt-1 text-sm text-muted-foreground">Выберите параметры и нажмите «Сформировать».</p></section>
            </div>
        </div>
    </AppLayout>
</template>
