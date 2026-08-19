<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { IconBook2, IconPlus, IconRefresh, IconTrash } from '@tabler/icons-vue';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

interface KnowledgeItem {
    id: string;
    title: string;
    category: string;
    content: string;
    is_active: boolean;
}

const props = defineProps<{ items: KnowledgeItem[] }>();
const title = ref('');
const category = ref('Курсы');
const content = ref('');
const saving = ref(false);
const seeding = ref(false);

const addItem = () => {
    if (!title.value.trim() || !content.value.trim() || saving.value) return;
    saving.value = true;
    router.post('/knowledge-base', {
        title: title.value,
        category: category.value,
        content: content.value,
        is_active: true,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            title.value = '';
            content.value = '';
        },
        onFinish: () => {
            saving.value = false;
        },
    });
};

const seed = () => {
    if (seeding.value) return;
    seeding.value = true;
    router.post('/knowledge-base/seed-orkendey', {}, {
        preserveScroll: true,
        onFinish: () => {
            seeding.value = false;
        },
    });
};

const toggle = (item: KnowledgeItem) => {
    router.put(`/knowledge-base/${item.id}`, {
        title: item.title,
        category: item.category,
        content: item.content,
        is_active: !item.is_active,
    }, { preserveScroll: true });
};

const remove = (item: KnowledgeItem) => {
    router.delete(`/knowledge-base/${item.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="База знаний" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col p-4 md:p-6">
            <div class="mx-auto w-full max-w-6xl space-y-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full border bg-violet-50 px-3 py-1 text-xs font-bold">
                            <IconBook2 class="size-4" />
                            Проверенные данные для AI
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight">База знаний Өркендеу</h1>
                        <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                            Активные записи автоматически добавляются к AI-запросу. Здесь храните точные сведения о курсах, услугах, контактах и правилах формулировок.
                        </p>
                    </div>
                    <Button variant="outline" class="gap-2" :disabled="seeding" @click="seed">
                        <IconRefresh class="size-4" />
                        {{ seeding ? 'Заполняю…' : 'Заполнить данными Өркендеу' }}
                    </Button>
                </div>

                <section class="rounded-2xl border-2 border-foreground bg-card p-5 shadow-2xs">
                    <h2 class="font-bold">Добавить проверенный факт</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-[1fr_220px]">
                        <input v-model="title" class="h-10 rounded-md border-2 border-foreground bg-background px-3 text-sm" placeholder="Например: Курс по инклюзивному образованию" />
                        <select v-model="category" class="h-10 rounded-md border-2 border-foreground bg-background px-3 text-sm">
                            <option>Компания</option>
                            <option>Контакты</option>
                            <option>Курсы</option>
                            <option>Продукты</option>
                            <option>Цены</option>
                            <option>Нормативные данные</option>
                            <option>Стиль</option>
                            <option>Другое</option>
                        </select>
                    </div>
                    <textarea v-model="content" class="mt-3 min-h-32 w-full rounded-md border-2 border-foreground bg-background p-3 text-sm" placeholder="Введите точную информацию, которую AI имеет право использовать без догадок." />
                    <div class="mt-3 flex justify-end">
                        <Button class="gap-2" :disabled="saving || !title.trim() || !content.trim()" @click="addItem">
                            <IconPlus class="size-4" />
                            {{ saving ? 'Сохраняю…' : 'Добавить' }}
                        </Button>
                    </div>
                </section>

                <section v-if="props.items.length" class="grid gap-4 lg:grid-cols-2">
                    <article v-for="item in props.items" :key="item.id" class="rounded-2xl border bg-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-bold">{{ item.category }}</span>
                                    <span :class="item.is_active ? 'bg-green-100 text-green-900' : 'bg-gray-100 text-gray-600'" class="rounded-full px-2.5 py-1 text-[11px] font-bold">
                                        {{ item.is_active ? 'Используется AI' : 'Отключено' }}
                                    </span>
                                </div>
                                <h3 class="mt-3 font-bold">{{ item.title }}</h3>
                            </div>
                            <button type="button" class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-destructive" @click="remove(item)">
                                <IconTrash class="size-4" />
                            </button>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-foreground/80">{{ item.content }}</p>
                        <div class="mt-4 border-t pt-3">
                            <Button size="sm" variant="outline" @click="toggle(item)">
                                {{ item.is_active ? 'Не использовать в AI' : 'Включить в AI' }}
                            </Button>
                        </div>
                    </article>
                </section>

                <div v-else class="rounded-2xl border border-dashed p-10 text-center">
                    <IconBook2 class="mx-auto size-10 text-muted-foreground" />
                    <h2 class="mt-3 font-bold">База знаний пока пустая</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Нажмите «Заполнить данными Өркендеу» или добавьте первую запись вручную.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
