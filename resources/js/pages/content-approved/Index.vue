<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { IconCalendarClock, IconCheck, IconEye, IconPencil, IconSparkles, IconX } from '@tabler/icons-vue';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface MediaItem {
    url?: string;
    thumbnail_url?: string;
}

interface PostPlatform {
    content_type?: string | null;
    enabled?: boolean;
    social_account?: {
        display_label?: string;
        username?: string;
    } | null;
}

interface Post {
    id: string;
    content: string;
    media: MediaItem[];
    scheduled_at: string | null;
    post_platforms: PostPlatform[];
}

interface PaginatedPosts {
    data: Post[];
    total: number;
}

const props = defineProps<{ posts: PaginatedPosts }>();
const posts = computed(() => props.posts.data ?? []);
const previewPost = ref<Post | null>(null);
const time = ref('10:00');
const scheduling = ref(false);

const mediaUrl = (post: Post) => post.media?.[0]?.thumbnail_url ?? post.media?.[0]?.url ?? null;

const formatDate = (value: string | null) => {
    if (!value) return 'Без даты';
    return new Intl.DateTimeFormat('ru-RU', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        timeZone: 'Asia/Almaty',
    }).format(new Date(value));
};

const typeLabel = (post: Post) => {
    const type = post.post_platforms?.find((item) => item.content_type)?.content_type;
    if (type === 'instagram_story') return 'Stories';
    if (type === 'instagram_reel') return 'Reels';
    return post.media?.length > 1 ? 'Карусель' : 'Пост';
};

const accountLabel = (post: Post) => {
    const account = post.post_platforms?.find((item) => item.social_account)?.social_account;
    return account?.username ? `@${account.username}` : account?.display_label ?? 'Instagram';
};

const scheduleAll = () => {
    if (!props.posts.total || scheduling.value) return;
    scheduling.value = true;
    router.post('/content-approved/schedule-all', { time: time.value }, {
        onFinish: () => {
            scheduling.value = false;
        },
    });
};
</script>

<template>
    <Head title="Одобрено" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4 md:p-6">
            <div class="mx-auto w-full max-w-6xl space-y-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full border bg-green-50 px-3 py-1 text-xs font-bold text-green-900">
                            <IconCheck class="size-4" />
                            Готово к планированию
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">Одобрено</h1>
                        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                            Эти материалы уже прошли проверку. Выберите время по Алматы и поставьте всю неделю в штатное расписание публикаций.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-end gap-2 rounded-2xl border bg-card p-3">
                        <label class="space-y-1">
                            <span class="block text-xs font-bold text-muted-foreground">Время публикации</span>
                            <input v-model="time" type="time" class="h-10 rounded-md border-2 border-foreground bg-background px-3 text-sm" />
                        </label>
                        <Button class="gap-2" :disabled="!props.posts.total || scheduling" @click="scheduleAll">
                            <IconCalendarClock class="size-4" />
                            {{ scheduling ? 'Планирую…' : 'Запланировать всё' }}
                        </Button>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 text-sm">
                    <span>Одобренных материалов: <strong>{{ props.posts.total }}</strong></span>
                    <Link href="/content-approval" class="font-semibold hover:underline">Вернуться к согласованию</Link>
                </div>

                <div v-if="posts.length" class="grid gap-4 lg:grid-cols-2">
                    <article v-for="post in posts" :key="post.id" class="overflow-hidden rounded-2xl border-2 border-foreground bg-card shadow-2xs">
                        <div class="grid sm:grid-cols-[150px_1fr]">
                            <div class="min-h-40 bg-muted">
                                <img v-if="mediaUrl(post)" :src="mediaUrl(post)!" alt="Превью" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full min-h-40 items-center justify-center text-muted-foreground">
                                    <IconSparkles class="size-8" />
                                </div>
                            </div>
                            <div class="flex min-w-0 flex-col p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold text-green-900">Одобрено</span>
                                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-bold">{{ typeLabel(post) }}</span>
                                </div>
                                <p class="mt-3 line-clamp-4 whitespace-pre-line text-sm leading-relaxed">{{ post.content }}</p>
                                <div class="mt-auto pt-4 text-xs text-muted-foreground">
                                    <span>{{ formatDate(post.scheduled_at) }}</span>
                                    <span class="mx-2">•</span>
                                    <span>{{ accountLabel(post) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 border-t p-3">
                            <Button size="sm" variant="outline" class="gap-1.5" @click="previewPost = post">
                                <IconEye class="size-4" />
                                Посмотреть
                            </Button>
                            <Link :href="`/posts/${post.id}/edit`" class="inline-flex h-9 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-muted">
                                <IconPencil class="size-4" />
                                Изменить
                            </Link>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-2xl border border-dashed p-12 text-center">
                    <IconCheck class="mx-auto size-10 text-muted-foreground" />
                    <h2 class="mt-3 text-lg font-bold">Нет одобренных черновиков</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Одобрите материалы на предыдущем этапе или сформируйте новую неделю.</p>
                    <div class="mt-4 flex justify-center gap-2">
                        <Link href="/content-approval" class="inline-flex h-10 items-center rounded-md border px-4 text-sm font-semibold hover:bg-muted">На согласовании</Link>
                        <Link href="/content-plan" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground">Контент-план</Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="previewPost" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="previewPost = null">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border-2 border-foreground bg-background shadow-xl">
                <div class="sticky top-0 flex items-center justify-between border-b bg-background p-4">
                    <div>
                        <p class="font-bold">Предпросмотр</p>
                        <p class="text-xs text-muted-foreground">{{ formatDate(previewPost.scheduled_at) }} · {{ typeLabel(previewPost) }}</p>
                    </div>
                    <button type="button" class="rounded-md p-2 hover:bg-muted" @click="previewPost = null"><IconX class="size-5" /></button>
                </div>
                <div class="space-y-4 p-5">
                    <img v-if="mediaUrl(previewPost)" :src="mediaUrl(previewPost)!" alt="Превью" class="mx-auto max-h-[420px] rounded-xl border object-contain" />
                    <p class="whitespace-pre-line text-sm leading-6">{{ previewPost.content }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
