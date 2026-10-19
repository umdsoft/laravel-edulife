<script setup>
import { computed, ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Badge, Button, Card, EmptyState, FormField, Input, Modal, PageHeader, Radio, Textarea,
} from '@/Components/UI';
import { PaperAirplaneIcon, RectangleStackIcon, BellIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    templates: Array,
});

const AUDIENCES = [
    { value: 'all', label: 'Barcha foydalanuvchilar', short: 'Barchaga', description: "O'quvchilar, ustozlar va adminlar", variant: 'brand' },
    { value: 'students', label: "Faqat o'quvchilar", short: "O'quvchilarga", description: "Talaba rolidagi foydalanuvchilar", variant: 'info' },
    { value: 'teachers', label: 'Faqat ustozlar', short: 'Ustozlarga', description: "O'qituvchi rolidagi foydalanuvchilar", variant: 'success' },
];
const audienceOf = (v) => AUDIENCES.find((a) => a.value === v) || AUDIENCES[0];

const selectedTemplate = ref(null);

const form = useForm({
    title: '',
    message: '',
    type: 'all',
    user_ids: [],
});

const applyTemplate = (t) => {
    selectedTemplate.value = t;
    if (t) {
        form.title = t.title;
        form.message = t.message;
        form.type = t.type;
    }
};

const canSend = computed(() => form.title.trim() && form.message.trim());
const confirmOpen = ref(false);
const requestSend = () => {
    if (!canSend.value) return;
    confirmOpen.value = true;
};

const submit = () => {
    form.post(route('admin.notifications.send'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedTemplate.value = null;
        },
        onFinish: () => (confirmOpen.value = false),
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Xabar yuborish" />

        <PageHeader title="Xabar yuborish" description="Foydalanuvchilarga tizim xabarnomasi yuboring">
            <template #actions>
                <Button variant="secondary" :icon-left="RectangleStackIcon" :href="route('admin.notifications.templates')">Shablonlar</Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Yangi xabar">
                    <form id="send-form" class="space-y-5" @submit.prevent="requestSend">
                        <Input v-model="form.title" label="Sarlavha" required maxlength="255" placeholder="Masalan: Shanba imtihoni vaqti o'zgardi"
                            :error="form.errors.title" />
                        <Textarea v-model="form.message" label="Xabar matni" required :rows="6" autoresize
                            placeholder="Xabar matnini kiriting" :error="form.errors.message" />

                        <FormField as="fieldset" label="Kimga" :error="form.errors.type">
                            <div class="grid gap-2 sm:grid-cols-3">
                                <div v-for="a in AUDIENCES" :key="a.value" :class="[
                                    'flex cursor-pointer rounded-md border p-3 transition-colors',
                                    form.type === a.value ? 'border-brand-500 bg-brand-50' : 'border-border hover:bg-surface-2',
                                ]" @click="form.type = a.value">
                                    <Radio v-model="form.type" :value="a.value" name="audience" :label="a.label" :description="a.description" />
                                </div>
                            </div>
                        </FormField>
                    </form>
                    <template #footer>
                        <div class="flex justify-end">
                            <Button type="submit" form="send-form" :icon-left="PaperAirplaneIcon" :disabled="!canSend" :loading="form.processing">
                                Yuborish
                            </Button>
                        </div>
                    </template>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Ko'rinishi" description="Foydalanuvchi bildirishnomalarda shunday ko'radi">
                    <div class="flex gap-3 rounded-md border border-border bg-surface-2 p-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand">
                            <BellIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-fg">{{ form.title || 'Xabar sarlavhasi' }}</p>
                                <span class="shrink-0 text-xs text-fg-subtle">hozir</span>
                            </div>
                            <p class="mt-0.5 line-clamp-4 whitespace-pre-line text-13 text-fg-muted">
                                {{ form.message || 'Xabar matni shu yerda ko\'rinadi' }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-13 text-fg-subtle">
                        Qabul qiluvchilar: <Badge :variant="audienceOf(form.type).variant" size="sm">{{ audienceOf(form.type).short }}</Badge>
                    </p>
                </Card>

                <Card padding="none" title="Tayyor shablonlar">
                    <ul v-if="templates?.length" class="max-h-96 divide-y divide-border overflow-y-auto">
                        <li v-for="t in templates" :key="t.id">
                            <button type="button" :aria-pressed="selectedTemplate?.id === t.id" :class="[
                                'focus-ring flex w-full items-start gap-3 px-5 py-3 text-left transition-colors',
                                selectedTemplate?.id === t.id ? 'bg-brand-50' : 'hover:bg-surface-2',
                            ]" @click="applyTemplate(t)">
                                <DocumentTextIcon class="mt-0.5 h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-fg">{{ t.name }}</span>
                                    <span class="block truncate text-13 text-fg-subtle">{{ t.title }}</span>
                                </span>
                                <Badge :variant="audienceOf(t.type).variant" size="sm">{{ audienceOf(t.type).short }}</Badge>
                            </button>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="RectangleStackIcon" title="Faol shablon yo'q" size="sm" class="py-6">
                        <Button size="sm" variant="secondary" :href="route('admin.notifications.templates')">Shablon yaratish</Button>
                    </EmptyState>
                </Card>
            </aside>
        </div>

        <Modal :show="confirmOpen" size="sm" title="Xabarni yuborish" @close="confirmOpen = false">
            <p class="text-sm text-fg-muted">
                “<span class="font-medium text-fg">{{ form.title }}</span>” xabari
                <span class="font-medium text-fg">{{ audienceOf(form.type).label.toLowerCase() }}</span>ga yuboriladi.
                Yuborilgan xabarni qaytarib olib bo'lmaydi.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="form.processing" @click="confirmOpen = false">Bekor qilish</Button>
                    <Button :icon-left="PaperAirplaneIcon" :loading="form.processing" @click="submit">Yuborish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
