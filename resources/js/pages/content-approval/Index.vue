<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    IconCheck,
    IconChecks,
    IconEye,
    IconPencil,
    IconRefresh,
    IconSparkles,
    IconX,
} from '@tabler/icons-vue';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface MediaItem {
    id?: string;
    url?: string;
    thumbnail_url?: string;
    mime_type?: string;
}

interface PostPlatform {
    platform?: string;
    content_type?: string | null;
    social_account?: {
        display_label?: string;
        username?: string;
    } | null;
}

interface Post {
    id: string;
    content: string;
    media: MediaItem[];
    status: string;
    scheduled_at: string | null;
    post_platforms: PostPlatform[];
}

interface PaginatedPosts {
    data: Post[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{ posts: PaginatedPosts }>();
const previewPost = ref<Post | null>(null);
const approvingId = ref<string | null>(null);
const approvingAll = ref(false);

const posts = computed(() => props.posts.data ?? []);

const formatDate = (value: string | null) => {
    if (!value) return 'Без даты';
    return new Intl.DateTimeFormat('ru-RU', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
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

const mediaUrl = (post: Post) => post.media?.[0]?.thumbnail_url ?? post.media?.[0]?.url ?? null;

const approve = (post: Post) => {
    if (approvingId.value || approvingAll.value) return;
    approvingId.value = post.id;

    router.post(`/content-approval/${post.id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => {
            approvingId.value = null;
        },
    });
};

const approveAll = () => {
    if (approvingAll.value || props.posts.total === 0) return;
    approvingAll.value = true;
    router.post('/content-approval/approve-all', {}, {
        onFinish: () => {
            approvingAll.value = false;
        },
    });
};
</script>

<template>
    <Head title="На согласовании" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4 md:p-6">
            <div class="mx-auto w-full max-w-6xl space-y-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full border bg-amber-50 px-3 py-1 text-xs font-bold">
                            <IconSparkles class="size-4" />
                            Проверка перед публикацией
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">На согласовании</h1>
                        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                            Проверьте AI-материалы. Одобрение не публикует пост — материал перейдёт в отдельный список «Одобрено».
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="rounded-xl border bg-card px-4 py-3 text-sm">
                            <span class="text-muted-foreground">Ожидают:</span>
                            <strong class="ml-2">{{ props.posts.total }}</strong>
                        </div>
                        <Button v-if="props.posts.total" class="gap-2" :disabled="approvingAll" @click="approveAll">
                            <IconChecks class="size-4" />
                            {{ approvingAll ? 'Одобряю…' : 'Одобрить всё' }}
                        </Button>
                        <Link
                            href="/content-approved"
                            class="inline-flex h-10 items-center rounded-md border bg-background px-4 text-sm font-semibold hover:bg-muted"
                        >
                            Одобренные
                        </Link>
                    </div>
                </div>

                <div v-if="posts.length" class="grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="post in posts"
                        :key="post.id"
                        class="overflow-hidden rounded-2xl border-2 border-foreground bg-card shadow-2xs"
                    >
                        <div class="grid sm:grid-cols-[150px_1fr]">
                            <div class="min-h-40 bg-muted">
                                <img
                                    v-if="mediaUrl(post)"
                                    :src="mediaUrl(post)!"
                                    alt="Превью материала"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full min-h-40 items-center justify-center text-muted-foreground">
                                    <IconSparkles class="size-8" />
                                </div>
                            </div>

                            <div class="flex min-w-0 flex-col p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-900">На согласовании</span>
                                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-bold">{{ typeLabel(post) }}</span>
                                </div>

                                <p class="mt-3 line-clamp-4 whitespace-pre-line text-sm leading-relaxed">
                                    {{ post.content || 'AI-материал без текстовой подписи' }}
                                </p>

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

                            <Link
                                :href="`/posts/${post.id}/edit`"
                                class="inline-flex h-9 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-muted"
                            >
                                <IconPencil class="size-4" />
                                Изменить
                            </Link>

                            <Link
                                :href="`/posts/${post.id}/edit`"
                                class="inline-flex h-9 items-center gap-1.5 rounded-md border bg-background px-3 text-sm font-medium hover:bg-muted"
                            >
                                <IconRefresh class="size-4" />
                                Переделать с AI
                            </Link>

                            <Button
                                size="sm"
                                class="ml-auto gap-1.5"
                                :disabled="approvingId === post.id || approvingAll"
                                @click="approve(post)"
                            >
                                <IconCheck class="size-4" />
                                {{ approvingId === post.id ? 'Одобряю…' : 'Одобрить' }}
                            </Button>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-2xl border border-dashed p-12 text-center">
                    <IconCheck class="mx-auto size-10 text-muted-foreground" />
                    <h2 class="mt-3 text-lg font-bold">Всё проверено</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Сейчас нет материалов, ожидающих согласования.</p>
                    <div class="mt-4 flex justify-center gap-2">
                        <Link href="/content-plan" class="inline-flex h-10 items-center rounded-md border px-4 text-sm font-semibold hover:bg-muted">
                            Контент-план
                        </Link>
                        <Link href="/content-approved" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-semibold text-primary-foreground">
                            Одобренные
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="previewPost"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            @click.self="previewPost = null"
        >
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border-2 border-foreground bg-background shadow-xl">
                <div class="sticky top-0 flex items-center justify-between border-b bg-background p-4">
                    <div>
                        <p class="font-bold">Предпросмотр</p>
                        <p class="text-xs text-muted-foreground">{{ formatDate(previewPost.scheduled_at) }} · {{ typeLabel(previewPost) }}</p>
                    </div>
                    <button type="button" class="rounded-md p-2 hover:bg-muted" @click="previewPost = null">
                        <IconX class="size-5" />
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <img
                        v-if="mediaUrl(previewPost)"
                        :src="mediaUrl(previewPost)!"
                        alt="Превью"
                        class="mx-auto max-h-[420px] rounded-xl border object-contain"
                    />
                    <p class="whitespace-pre-line text-sm leading-6">{{ previewPost.content }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
