<script setup lang="ts">
import dayjs from '@/dayjs';

defineProps<{
    title?: string;
}>();

const currentYear = dayjs().year();
</script>

<template>
    <div class="min-h-svh bg-background">
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold tracking-tight">Orkendey AI SMM</span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-12">
            <h1 v-if="title" class="mb-8 text-3xl font-bold">{{ title }}</h1>
            <slot />
        </main>

        <footer class="mt-auto border-t">
            <div class="mx-auto max-w-5xl px-6 py-8">
                <p class="text-center text-sm text-muted-foreground">
                    &copy; {{ currentYear }} Orkendey AI SMM
                </p>
            </div>
        </footer>
    </div>
</template>
