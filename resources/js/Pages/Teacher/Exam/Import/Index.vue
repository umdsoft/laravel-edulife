<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ArrowUpTrayIcon, DocumentTextIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Alert from '@/Components/UI/Alert.vue';
import { formatNumber, formatSize } from '@/Components/Teacher/format';

const props = defineProps({
    columns: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 500 },
    maxFileKb: { type: Number, default: 4096 },
    importedInReview: { type: Number, default: 0 },
});

const form = useForm({ file: null });
const input = ref(null);
const dragging = ref(false);
const pick = (file) => {
    form.clearErrors();
    form.file = file || null;
};
const onDrop = (e) => {
    dragging.value = false;
    pick(e.dataTransfer?.files?.[0]);
};
const submit = () => form.file && form.post(route('teacher.exam.import.upload'), { forceFormData: true });

const HELP = [
    ['subject', 'math yoki physics'],
    ['topic', 'mavzu kodi (math.equations.quadratic) yoki nomi'],
    ['type', 'single_choice, multiple_choice, numeric, numeric_with_unit'],
    ['difficulty', '1–5'],
    ['question', 'shart; formulalar $…$ va $$…$$'],
    ['option_a … option_e', 'variantlar (kamida A–D)'],
    ['correct', 'harf (B), bir nechta (A,C) yoki son (20; 0,5; 1/2)'],
    ['unit, tolerance, tolerance_type, accepted_units', 'son javob: birlik, xatolik (abs | rel), km/soat=0.2777777778'],
    ['source_ref, exam_formats', 'manba; milliy_sertifikat; abituriyent; sat'],
    ['solution_text, solution_video_url, units_note', 'yechim, video havolasi, birlik izohi'],
];
</script>

<template>
    <Head title="Import — Savollar banki" />

    <TeacherLayout>
        <PageHeader title="Savollarni import qilish" description="CSV, Excel (xlsx) yoki JSON. Savollar tekshiruv navbatiga tushadi."
            :breadcrumbs="[{ label: 'Savollar banki', href: route('teacher.exam.questions.index') }, { label: 'Import' }]">
            <template #actions>
                <Button v-if="importedInReview" variant="secondary" :icon-left="ClipboardDocumentCheckIcon" :href="route('teacher.exam.review', { origin: 'import' })">
                    Import qilinganlar navbatda: {{ formatNumber(importedInReview) }}
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="1. Faylni yuklang">
                    <form @submit.prevent="submit">
                        <label :class="[
                            'flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed px-6 py-10 text-center transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500',
                            dragging ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:bg-surface-2',
                        ]" @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="onDrop">
                            <ArrowUpTrayIcon class="h-8 w-8 text-fg-subtle" aria-hidden="true" />
                            <span v-if="!form.file" class="text-sm font-medium text-fg">Faylni shu yerga tashlang yoki tanlang</span>
                            <span v-else class="flex items-center gap-2 text-sm font-medium text-fg">
                                <DocumentTextIcon class="h-5 w-5 text-brand" aria-hidden="true" />{{ form.file.name }}
                                <span class="font-normal text-fg-subtle">{{ formatSize(form.file.size) }}</span>
                            </span>
                            <span class="text-13 text-fg-subtle">.csv, .xlsx, .xls, .ods, .json · ko'pi bilan {{ formatNumber(maxRows) }} ta savol, {{ formatSize(maxFileKb * 1024) }}</span>
                            <input ref="input" type="file" class="sr-only" accept=".csv,.txt,.xlsx,.xls,.ods,.json"
                                @change="pick($event.target.files?.[0])" />
                        </label>
                        <Alert v-if="form.errors.file" variant="danger" class="mt-4">{{ form.errors.file }}</Alert>
                        <div class="mt-4 flex justify-end">
                            <Button type="submit" :disabled="!form.file" :loading="form.processing">Tekshirish</Button>
                        </div>
                    </form>
                </Card>

                <Card title="2. Oldindan ko'rish va tasdiqlash">
                    <ul class="list-disc space-y-1.5 pl-5 text-sm text-fg-muted">
                        <li>Har qator tekshiriladi: xatolar, ogohlantirishlar va dublikatlar (shart matni normallashtirib solishtiriladi — bo'shliq, katta-kichik harf, «0,5» = «0.5»).</li>
                        <li>Dublikatlar va xatoli qatorlar import qilinmaydi.</li>
                        <li>Fayldagi to'g'ri javob <span class="font-medium text-fg">avtomatik qabul qilinmaydi</span>: uni oldindan ko'rish sahifasida alohida tasdiqlaysiz. Aks holda javobni tekshiruv navbatida belgilaysiz.</li>
                        <li>Import qilingan savollar har doim «Tekshiruvda» holatida — imtihonga faqat siz tasdiqlagandan keyin tushadi.</li>
                    </ul>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Shablonlar" description="Ustunlar nomi va namunaviy qatorlar bilan.">
                    <div class="flex flex-col gap-2">
                        <Button variant="secondary" :icon-left="ArrowDownTrayIcon" external :href="route('teacher.exam.import.template', { format: 'xlsx' })">Excel (.xlsx)</Button>
                        <Button variant="secondary" :icon-left="ArrowDownTrayIcon" external :href="route('teacher.exam.import.template', { format: 'csv' })">CSV</Button>
                        <Button variant="secondary" :icon-left="ArrowDownTrayIcon" external :href="route('teacher.exam.import.template', { format: 'json' })">JSON</Button>
                    </div>
                </Card>
                <Card title="Ustunlar">
                    <dl class="divide-y divide-border text-13">
                        <div v-for="[k, v] in HELP" :key="k" class="py-2">
                            <dt class="font-mono text-xs text-fg">{{ k }}</dt>
                            <dd class="mt-0.5 text-fg-muted">{{ v }}</dd>
                        </div>
                    </dl>
                </Card>
            </aside>
        </div>
    </TeacherLayout>
</template>
