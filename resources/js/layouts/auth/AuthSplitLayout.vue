<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh grid-cols-1 lg:grid-cols-2">
        <div class="flex min-w-0 flex-col gap-4 p-6 md:p-10">
            <div class="flex items-start">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-primary text-sm font-black text-primary-foreground"
                    >
                        O
                    </div>
                    <div>
                        <div class="text-lg font-bold tracking-tight">Orkendey AI SMM</div>
                        <div class="text-xs text-muted-foreground">AI-помощник для социальных сетей</div>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-lg">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col items-center gap-2 text-center">
                            <h1 v-if="title" class="text-2xl font-bold">{{ title }}</h1>
                            <p v-if="description" class="text-sm text-balance text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>

                        <slot />
                    </div>
                </div>
            </div>
        </div>

        <div
            class="relative hidden overflow-hidden border-l-2 border-foreground bg-accent lg:flex lg:min-h-svh lg:items-center lg:justify-center"
        >
            <div class="pointer-events-none absolute -right-24 -top-24 size-[440px] rounded-full bg-violet-200/50 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-32 -left-32 size-[440px] rounded-full bg-fuchsia-200/40 blur-3xl" />

            <div class="relative mx-auto max-w-lg px-12 text-center">
                <div
                    class="mx-auto mb-8 flex size-24 items-center justify-center rounded-3xl border-2 border-foreground bg-card text-4xl font-black shadow-xl"
                >
                    O
                </div>
                <h2 class="text-4xl font-black tracking-tight text-foreground">
                    Ваш AI-SMM сотрудник
                </h2>
                <p class="mx-auto mt-5 max-w-md text-base leading-relaxed text-foreground/70">
                    Планируйте контент, создавайте посты, карусели, Reels и Stories,
                    согласовывайте материалы и публикуйте их из одного кабинета.
                </p>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <div class="rounded-xl border-2 border-foreground bg-card px-4 py-3 text-sm font-semibold shadow-sm">
                        Instagram
                    </div>
                    <div class="rounded-xl border-2 border-foreground bg-card px-4 py-3 text-sm font-semibold shadow-sm">
                        AI-контент
                    </div>
                    <div class="rounded-xl border-2 border-foreground bg-card px-4 py-3 text-sm font-semibold shadow-sm">
                        Согласование
                    </div>
                    <div class="rounded-xl border-2 border-foreground bg-card px-4 py-3 text-sm font-semibold shadow-sm">
                        Аналитика
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
