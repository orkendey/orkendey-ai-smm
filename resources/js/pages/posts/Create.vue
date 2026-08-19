<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    IconBookmarks,
    IconBriefcase,
    IconBuildingCommunity,
    IconFlame,
    IconHeartHandshake,
    IconPencil,
    IconShieldCheck,
    IconSparkles,
    IconUsersGroup,
} from '@tabler/icons-vue';
import { computed, ref } from 'vue';

import { index as templatesIndex } from '@/actions/App/Http/Controllers/App/PostTemplateController';
import PageHeader from '@/components/PageHeader.vue';
import AiPostWizard from '@/components/posts/create/AiPostWizard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { store as storePost } from '@/routes/app/posts';

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
    date?: string | null;
    socialAccounts: SocialAccount[];
    templates: AiTemplate[];
}

const props = withDefaults(defineProps<Props>(), {
    date: null,
});

type View = 'choice' | 'ai';

const view = ref<View>('choice');
const submitting = ref(false);
const selectedPrompt = ref('');
const aiHeader = ref<{ title: string; description: string } | null>(null);

const hasConnectedAccounts = computed(() => props.socialAccounts.length > 0);

const topics = [
    {
        title: 'Повышение квалификации',
        description: 'Курсы для педагогов, программы и преимущества обучения',
        icon: IconBriefcase,
        prompt: 'Создай продающий и экспертный Instagram-контент для учебно-методического центра «Өркендеу» о курсах повышения квалификации педагогов в Казахстане. Тон профессиональный, понятный и доверительный. Не придумывай нормативные факты, которых нет в исходных данных.',
    },
    {
        title: 'Инклюзивное образование',
        description: 'Курсы, практика, сопровождение педагогов и ДОУ',
        icon: IconHeartHandshake,
        prompt: 'Создай экспертный Instagram-контент для «Өркендеу» по теме инклюзивного образования и повышения квалификации педагогов. Сделай акцент на практической пользе для педагогов и организаций образования Казахстана.',
    },
    {
        title: 'Буллинг',
        description: 'Профилактика, обучение педагогов и работа с коллективом',
        icon: IconUsersGroup,
        prompt: 'Создай Instagram-контент для «Өркендеу» по теме профилактики буллинга в организациях образования. Аудитория — педагоги и руководители школ/колледжей. Контент должен быть полезным, профессиональным и без запугивания.',
    },
    {
        title: 'БиОТ',
        description: 'Безопасность и охрана труда для организаций',
        icon: IconShieldCheck,
        prompt: 'Создай Instagram-контент для «Өркендеу» по теме безопасности и охраны труда (БиОТ) для организаций Казахстана. Объясняй простым языком, делай акцент на практической подготовке сотрудников и пользе обучения.',
    },
    {
        title: 'Антитеррор',
        description: 'Подготовка персонала и требования безопасности',
        icon: IconShieldCheck,
        prompt: 'Создай профессиональный Instagram-контент для «Өркендеу» по теме антитеррористической подготовки персонала организаций. Избегай неподтвержденных юридических утверждений и делай акцент на обучении и подготовке сотрудников.',
    },
    {
        title: 'ПТМ',
        description: 'Пожарно-технический минимум и подготовка сотрудников',
        icon: IconFlame,
        prompt: 'Создай Instagram-контент для «Өркендеу» по теме пожарной безопасности и обучения ПТМ. Аудитория — руководители организаций и сотрудники, отвечающие за безопасность. Тон деловой и понятный.',
    },
    {
        title: 'Лицензирование ДОУ',
        description: 'Документы, подготовка и методические материалы',
        icon: IconBuildingCommunity,
        prompt: 'Создай Instagram-контент для «Өркендеу» по теме подготовки детских садов к лицензированию образовательной деятельности в Казахстане. Подчеркни пользу систематизированных документов и необходимость актуализировать их под конкретную организацию.',
    },
    {
        title: 'Другая тема',
        description: 'Начать с пустого запроса и описать задачу самостоятельно',
        icon: IconSparkles,
        prompt: '',
    },
];

const startAi = (prompt: string) => {
    selectedPrompt.value = prompt;
    view.value = 'ai';
};

const startFromScratch = () => {
    if (submitting.value) return;
    submitting.value = true;
    const url = props.date ? storePost.url({ query: { date: props.date } }) : storePost.url();
    router.post(url, {}, {
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const pageTitle = computed(() => 'AI SMM-агент');

const stepHeader = computed(() => {
    if (view.value === 'ai' && aiHeader.value) return aiHeader.value;
    return {
        title: 'AI SMM-агент',
        description: 'Выберите направление — агент подготовит запрос для генерации Instagram-контента в стиле «Өркендеу».',
    };
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4 md:p-6">
            <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <PageHeader :title="stepHeader.title" :description="stepHeader.description" />

                <template v-if="view === 'choice'">
                    <div
                        v-if="!hasConnectedAccounts"
                        class="rounded-2xl border-2 border-amber-400 bg-amber-50 p-4 text-sm text-amber-950"
                    >
                        Для генерации и публикации сначала подключите Instagram-аккаунт в разделе «Подключения». Темы уже можно просмотреть, но AI-мастер станет доступен после подключения аккаунта.
                    </div>

                    <section class="space-y-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-muted-foreground">Шаг 1</p>
                            <h2 class="text-xl font-bold">Что будем продвигать?</h2>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <button
                                v-for="topic in topics"
                                :key="topic.title"
                                type="button"
                                class="group flex min-h-40 flex-col items-start justify-between rounded-2xl border-2 border-foreground bg-card p-4 text-left shadow-2xs transition-all hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!hasConnectedAccounts"
                                @click="startAi(topic.prompt)"
                            >
                                <div class="inline-flex size-10 items-center justify-center rounded-xl border-2 border-foreground bg-violet-100 transition-transform group-hover:-rotate-2">
                                    <component :is="topic.icon" class="size-5" />
                                </div>
                                <div class="mt-4">
                                    <p class="font-bold">{{ topic.title }}</p>
                                    <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ topic.description }}</p>
                                </div>
                            </button>
                        </div>
                    </section>

                    <section class="rounded-2xl border bg-muted/30 p-4">
                        <div class="mb-3">
                            <p class="text-sm font-semibold">Другие способы создания</p>
                            <p class="text-xs text-muted-foreground">Ручной редактор и готовые шаблоны остаются доступны.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                class="flex items-center gap-3 rounded-xl border bg-card p-4 text-left hover:bg-muted/50 disabled:opacity-50"
                                :disabled="submitting"
                                @click="startFromScratch"
                            >
                                <IconPencil class="size-5" />
                                <div>
                                    <p class="text-sm font-bold">Создать вручную</p>
                                    <p class="text-xs text-muted-foreground">Пустой пост без AI</p>
                                </div>
                            </button>

                            <Link
                                :href="templatesIndex.url({ query: { date: props.date } })"
                                class="flex items-center gap-3 rounded-xl border bg-card p-4 text-left hover:bg-muted/50"
                            >
                                <IconBookmarks class="size-5" />
                                <div>
                                    <p class="text-sm font-bold">Шаблоны</p>
                                    <p class="text-xs text-muted-foreground">Использовать готовую структуру</p>
                                </div>
                            </Link>
                        </div>
                    </section>
                </template>

                <AiPostWizard
                    v-else-if="view === 'ai'"
                    :social-accounts="socialAccounts"
                    :templates="templates"
                    :date="props.date"
                    :initial-prompt="selectedPrompt"
                    @update:step-header="aiHeader = $event"
                    @cancel="view = 'choice'; aiHeader = null; selectedPrompt = ''"
                />
            </div>
        </div>
    </AppLayout>
</template>
