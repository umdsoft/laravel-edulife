<script setup>
/**
 * Teacher "AI qoralamalar" (stage 4): the teacher's own manuals / problem
 * collections that AI drafts are built from, and a form to draft questions.
 */
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpTrayIcon, DocumentTextIcon, TrashIcon, ClipboardDocumentCheckIcon, BookOpenIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Alert, Badge, Button, Card, EmptyState, Input, PageHeader, Select, Switch, Tabs, Textarea } from '@/Components/UI';
import AiDraftRunForm from '@/Components/Exam/AiDrafts/AiDraftRunForm.vue';
import AiDraftRunsTable from '@/Components/Exam/AiDrafts/AiDraftRunsTable.vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    runs: { type: Object, required: true },
    settings: { type: Object, required: true },
    options: { type: Object, required: true },
});

const tab = ref(props.documents.length ? 'draft' : 'sources');
const tabs = [
    { key: 'draft', label: 'Qoralama yaratish' },
    { key: 'sources', label: `Manbalar (${props.documents.length})` },
];

// ---- upload ----
const mode = ref('file');
const fileInput = ref(null);
const upload = useForm({
    subject_id: props.options.subjects?.[0]?.id ?? null,
    topic_id: null,
    title: '',
    source_ref: '',
    file: null,
    content_text: '',
});
const subjectOptions = computed(() => props.options.subjects.map((s) => ({ value: s.id, label: s.name })));
const topicOptions = computed(() =>
    (props.options.subjects.find((s) => s.id === upload.subject_id)?.topics || [])
        .map((t) => ({ value: t.id, label: '  '.repeat(t.depth) + t.name })),
);
watch(() => upload.subject_id, () => (upload.topic_id = null));

const pickFile = (file) => {
    upload.clearErrors('file');
    upload.file = file || null;
    if (file && !upload.title) upload.title = file.name.replace(/\.[^.]+$/, '');
};
const submitUpload = () => {
    upload.transform((data) => (mode.value === 'file' ? { ...data, content_text: null } : { ...data, file: null }))
        .post(route('teacher.exam.ai-drafts.sources.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                upload.reset('title', 'source_ref', 'file', 'content_text');
                if (fileInput.value) fileInput.value.value = '';
            },
        });
};

const toggle = (doc) => router.patch(route('teacher.exam.ai-drafts.sources.toggle', doc.id), {}, { preserveScroll: true });
const remove = (doc) => {
    if (window.confirm(`«${doc.title}» manbasi o'chirilsinmi?`)) {
        router.delete(route('teacher.exam.ai-drafts.sources.destroy', doc.id), { preserveScroll: true });
    }
};
const kb = (n) => `${Math.max(1, Math.round((n || 0) / 1000)).toLocaleString('uz-UZ')} ming belgi`;
</script>

<template>
    <Head title="AI qoralamalar" />

    <TeacherLayout>
        <PageHeader title="AI qoralamalar"
            description="AI sizning qo'llanma va to'plamlaringizdan masala tuzadi. To'g'ri javobni siz belgilaysiz — tasdiqlanmagan savol imtihonga tushmaydi.">
            <template #meta>
                <Badge :variant="settings.configured ? 'success' : 'warning'" dot>{{ settings.configured ? 'AI ulangan' : 'AI sozlanmagan' }}</Badge>
            </template>
            <template #actions>
                <Button v-if="settings.review_url" variant="secondary" :icon-left="ClipboardDocumentCheckIcon" :href="settings.review_url">
                    Tekshiruv navbati
                </Button>
            </template>
        </PageHeader>

        <Tabs v-model="tab" :tabs="tabs" label="AI qoralama bo'limlari">
            <template #draft>
                <div class="grid gap-6 xl:grid-cols-3">
                    <Card title="Yangi so'rov" class="xl:col-span-1">
                        <AiDraftRunForm :options="options" :settings="settings" submit-route="teacher.exam.ai-drafts.run" />
                    </Card>
                    <Card title="Oxirgi so'rovlar" padding="none" class="xl:col-span-2">
                        <div class="p-4">
                            <AiDraftRunsTable :runs="runs" :show-pagination="false" />
                        </div>
                    </Card>
                </div>
            </template>

            <template #sources>
                <div class="grid gap-6 lg:grid-cols-3">
                    <Card title="Manba qo'shish" description="Qo'llanma yoki to'plam matni (.txt, .md, .tex, UTF-8). Formulalar $…$ ichida bo'lsa yaxshi.">
                        <form class="space-y-4" @submit.prevent="submitUpload">
                            <Select v-model="upload.subject_id" label="Fan" :options="subjectOptions" required :error="upload.errors.subject_id" />
                            <Select v-model="upload.topic_id" label="Mavzu" :options="topicOptions" placeholder="Butun fan bo'yicha"
                                :error="upload.errors.topic_id" />
                            <Input v-model="upload.title" label="Nomi" placeholder="Masalan: Rimkevich — Kinematika" required :error="upload.errors.title" />
                            <Input v-model="upload.source_ref" label="Manba belgisi" optional placeholder="Rimkevich, 2015" :error="upload.errors.source_ref" />

                            <div class="flex gap-2" role="group" aria-label="Manba turi">
                                <Button size="sm" :variant="mode === 'file' ? 'soft' : 'ghost'" @click="mode = 'file'">Fayl</Button>
                                <Button size="sm" :variant="mode === 'text' ? 'soft' : 'ghost'" @click="mode = 'text'">Matn joylash</Button>
                            </div>

                            <template v-if="mode === 'file'">
                                <label class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed border-border-strong px-4 py-8 text-center transition-colors duration-150 hover:bg-surface-2 focus-within:ring-2 focus-within:ring-brand-500">
                                    <ArrowUpTrayIcon class="h-7 w-7 text-fg-subtle" aria-hidden="true" />
                                    <span v-if="!upload.file" class="text-sm font-medium text-fg">Faylni tanlang</span>
                                    <span v-else class="flex items-center gap-2 text-sm font-medium text-fg">
                                        <DocumentTextIcon class="h-5 w-5 text-brand" aria-hidden="true" />{{ upload.file.name }}
                                    </span>
                                    <span class="text-13 text-fg-subtle">.txt, .md, .tex</span>
                                    <input ref="fileInput" type="file" class="sr-only" accept=".txt,.md,.tex,text/plain"
                                        @change="pickFile($event.target.files?.[0])" />
                                </label>
                                <Alert v-if="upload.errors.file" variant="danger">{{ upload.errors.file }}</Alert>
                            </template>
                            <Textarea v-else v-model="upload.content_text" label="Matn" :rows="8" autoresize
                                placeholder="12-masala. $3x + 5 = 20$ tenglamani yeching..." :error="upload.errors.content_text" />

                            <div class="flex justify-end">
                                <Button type="submit" :loading="upload.processing"
                                    :disabled="mode === 'file' ? !upload.file : !upload.content_text.trim()">Qo'shish</Button>
                            </div>
                        </form>
                    </Card>

                    <Card title="Manbalar" padding="none" class="lg:col-span-2">
                        <EmptyState v-if="!documents.length" :icon="BookOpenIcon" title="Hali manba yo'q"
                            description="AI masalani faqat siz yuklagan qo'llanma va to'plamlardan tuzadi." class="py-10" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="doc in documents" :key="doc.id" class="flex flex-wrap items-center gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-fg">{{ doc.title }}</p>
                                    <p class="mt-0.5 text-13 text-fg-muted">
                                        <Badge v-if="doc.subject" size="sm" :variant="doc.subject.code === 'physics' ? 'physics' : 'math'">{{ doc.subject.name }}</Badge>
                                        <span class="ml-1">{{ doc.topic || 'butun fan' }} · {{ kb(doc.char_count) }}</span>
                                        <span v-if="doc.source_ref"> · {{ doc.source_ref }}</span>
                                    </p>
                                </div>
                                <Switch :model-value="doc.is_active" :label="doc.is_active ? 'Faol' : 'O\'chiq'" @update:model-value="toggle(doc)" />
                                <Button size="sm" variant="ghost" :icon-left="TrashIcon" aria-label="O'chirish" @click="remove(doc)" />
                            </li>
                        </ul>
                    </Card>
                </div>
            </template>
        </Tabs>
    </TeacherLayout>
</template>
