<script setup>
import { reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { RectangleStackIcon, SparklesIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Alert, Badge, Button, Card, EmptyState, Input, PageHeader, Select } from '@/Components/UI';
import { LEVELS, LEVEL_LABELS } from '@/Utils/difficulty';
import OlympiadForm from '@/Components/Admin/OlympiadForm.vue';

const props = defineProps({
    olympiad: Object,
    form: Object, // current values in the form's shape (registration_fee, datetime-local strings, ...)
    sections: { type: Array, default: () => [] },
    types: Array,
    stages: Array,
    series: Array,
    regions: Array,
    bankLevels: { type: Object, default: () => ({}) }, // free approved questions per level 1..5
});

const form = useForm({ ...props.form });

const submit = () => {
    form.put(route('admin.olympiads.update', props.olympiad.id), { preserveScroll: true });
};

const SECTION_TYPES = { test: 'Test', open_ended: 'Ochiq savollar', practical: 'Amaliy', essay: 'Insho' };

// Questions come from the approved question bank of the olympiad's subject
const counts = reactive(Object.fromEntries(props.sections.map((s) => [s.id, 20])));
// difficulty is chosen explicitly (no default): a level 1..5 or "any"
const levels = reactive(Object.fromEntries(props.sections.map((s) => [s.id, null])));
const bankTotal = () => LEVELS.reduce((sum, l) => sum + Number(props.bankLevels?.[l] || 0), 0);
const levelOptions = () => [
    { value: 'any', label: `Istalgan (1–5) · ${bankTotal()} ta` },
    ...LEVELS.map((l) => ({ value: String(l), label: `${LEVEL_LABELS[l]} · ${Number(props.bankLevels?.[l] || 0)} ta` })),
];
const busy = ref(null);
const assign = (section) => router.post(route('admin.olympiads.sections.questions.assign', { olympiad: props.olympiad.id, section: section.id }),
    { count: counts[section.id], difficulty: levels[section.id] }, {
        preserveScroll: true,
        onStart: () => (busy.value = section.id),
        onFinish: () => (busy.value = null),
    });
const clear = (section) => router.delete(route('admin.olympiads.sections.questions.clear', { olympiad: props.olympiad.id, section: section.id }), {
    preserveScroll: true,
    onStart: () => (busy.value = section.id),
    onFinish: () => (busy.value = null),
});
</script>

<template>
    <AdminLayout>
        <Head :title="`${olympiad.title} — tahrirlash`" />

        <PageHeader title="Olimpiadani tahrirlash" :description="olympiad.title"
            :breadcrumbs="[
                { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
                { label: olympiad.title, href: route('admin.olympiads.show', olympiad.id) },
                { label: 'Tahrirlash' },
            ]" />

        <div class="space-y-6">
            <Alert v-if="!olympiad.editable" variant="warning" title="Tahrirlab bo'lmaydi">
                Jonli yoki yakunlangan olimpiadaning sozlamalari o'zgartirilmaydi.
            </Alert>

            <OlympiadForm :form="form" :types="types" :stages="stages" :series="series" :regions="regions" mode="edit"
                submit-label="Saqlash" :cancel-href="route('admin.olympiads.show', olympiad.id)" @submit="submit" />

            <Card id="sections" title="Bo'limlar" description="Olimpiada turiga ko'ra yaratilgan bo'limlar" padding="none"
                class="max-w-3xl scroll-mt-24 lg:ml-[204px]">
                <ul v-if="sections.length" class="divide-y divide-border">
                    <li v-for="s in sections" :key="s.id" class="space-y-3 px-5 py-4">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-fg">{{ s.title }}</p>
                                <p class="text-13 text-fg-subtle">{{ SECTION_TYPES[s.section_type] || s.section_type }}</p>
                            </div>
                            <span class="text-13 tabular-nums text-fg-muted">{{ s.duration_minutes }} daq</span>
                            <span class="text-13 tabular-nums text-fg-muted">{{ s.max_points }} ball · {{ s.weight_percent }}%</span>
                            <Badge :variant="s.questions_count ? 'success' : 'warning'" size="sm">{{ s.questions_count }} savol</Badge>
                        </div>
                        <div v-if="olympiad.editable" class="flex flex-wrap items-end gap-2">
                            <Input v-model.number="counts[s.id]" type="number" min="1" max="100" size="sm" label="Savollar soni"
                                class="w-32" inputmode="numeric" />
                            <Select v-model="levels[s.id]" :options="levelOptions()" placeholder="Tanlang" size="sm" label="Qiyinlik" required class="w-56" />
                            <Button size="sm" variant="secondary" :icon-left="SparklesIcon" :loading="busy === s.id" :disabled="!levels[s.id]" @click="assign(s)">
                                Savol bankidan qo'shish
                            </Button>
                            <Button v-if="s.questions_count" size="sm" variant="ghost" :icon-left="TrashIcon" :disabled="busy === s.id" @click="clear(s)">
                                Tozalash
                            </Button>
                        </div>
                    </li>
                </ul>
                <EmptyState v-else :icon="RectangleStackIcon" size="sm" title="Bo'limlar yo'q"
                    description="Olimpiada yaratishda «Standart bo'limlarni yaratish» yoqilmagan." />
                <p v-if="sections.length" class="border-t border-border px-5 py-3 text-13 text-fg-subtle">
                    Savollar tasdiqlangan test savollari bankidan (olimpiada fani bo'yicha, tanlangan qiyinlikda) tasodifiy tanlanadi; har biri 1 ball.
                </p>
            </Card>
        </div>
    </AdminLayout>
</template>
