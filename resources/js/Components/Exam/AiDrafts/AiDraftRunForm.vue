<script setup>
/**
 * Start an AI drafting run (stage 4): subject, topic, difficulty, format,
 * count and (optionally) the source documents to quote from.
 * Used by the admin "AI qoralamalar" page and the teacher's source page.
 */
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { SparklesIcon } from '@heroicons/vue/24/outline';
import { Alert, Button, Checkbox, FormField, Input, Select } from '@/Components/UI';

const props = defineProps({
    options: { type: Object, required: true }, // { subjects[{id,name,topics[]}], sources[], formats[] }
    settings: { type: Object, required: true }, // { configured, max_count, store_hint, ... }
    submitRoute: { type: String, required: true },
});

const form = useForm({
    subject_id: props.options.subjects?.[0]?.id ?? null,
    topic_id: null,
    difficulty_level: 3,
    exam_format: props.options.formats?.[0]?.value ?? null,
    count: Math.min(10, props.settings.max_count || 10),
    source_document_ids: [],
});

const subject = computed(() => props.options.subjects.find((s) => s.id === form.subject_id) || null);
const subjectOptions = computed(() => props.options.subjects.map((s) => ({ value: s.id, label: s.name })));
// native <select> collapses spaces: indent the tree with non-breaking spaces
const topicOptions = computed(() =>
    (subject.value?.topics || []).map((t) => ({ value: t.id, label: '  '.repeat(t.depth) + t.name })),
);
const sources = computed(() => props.options.sources.filter((d) => d.subject_id === form.subject_id));
const levels = [1, 2, 3, 4, 5].map((n) => ({ value: n, label: `${n} — ${['oson', 'oddiy', "o'rta", 'qiyin', 'olimpiada'][n - 1]}` }));

watch(() => form.subject_id, () => {
    form.topic_id = null;
    form.source_document_ids = [];
});

const submit = () => form.post(route(props.submitRoute), { preserveScroll: true, onSuccess: () => form.reset('source_document_ids') });
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <Alert v-if="!settings.configured" variant="warning" title="AI sozlanmagan">
            <code>ANTHROPIC_API_KEY</code> bo'sh — so'rov yuborilmaydi, ishga tushirish «o'tkazib yuborildi» deb yoziladi.
        </Alert>

        <div class="grid gap-4 sm:grid-cols-2">
            <Select v-model="form.subject_id" label="Fan" :options="subjectOptions" required :error="form.errors.subject_id" />
            <Select v-model="form.topic_id" label="Mavzu" :options="topicOptions" placeholder="Fanning istalgan mavzusi"
                :error="form.errors.topic_id" />
            <Select v-model="form.difficulty_level" label="Qiyinlik" :options="levels" required :error="form.errors.difficulty_level" />
            <Select v-model="form.exam_format" label="Format" :options="options.formats" placeholder="Umumiy"
                :error="form.errors.exam_format" />
            <Input v-model.number="form.count" type="number" min="1" :max="settings.max_count" label="Savollar soni"
                :hint="`Bir so'rovda ko'pi bilan ${settings.max_count} ta`" required :error="form.errors.count" />
        </div>

        <FormField as="fieldset" label="Manbalar" optional :error="form.errors.source_document_ids"
            hint="Belgilanmasa, mavzuga mos faol manbalar olinadi.">
            <p v-if="!sources.length" class="text-sm text-fg-subtle">Bu fan uchun manba yo'q — avval ustoz manba yuklashi kerak.</p>
            <div v-else class="grid gap-2 sm:grid-cols-2">
                <Checkbox v-for="doc in sources" :key="doc.id" v-model="form.source_document_ids" :value="doc.id"
                    :label="doc.title" :description="`${doc.char_count.toLocaleString('uz-UZ')} belgi`" />
            </div>
        </FormField>

        <p class="text-13 text-fg-muted">
            AI faqat shart va variantlarni tuzadi. To'g'ri javobni ustoz tekshiruv navbatida belgilaydi; tasdiqlanmagan savol imtihonga tushmaydi.
        </p>

        <div class="flex justify-end">
            <Button type="submit" :icon-left="SparklesIcon" :loading="form.processing" :disabled="!form.subject_id">
                Qoralama yaratish
            </Button>
        </div>
    </form>
</template>
