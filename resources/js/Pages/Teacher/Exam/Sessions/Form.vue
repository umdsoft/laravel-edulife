<script setup>
/**
 * Schedule / edit a Saturday session: subject, blueprint, date, time window and duration.
 */
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Alert, Button, Card, Input, PageHeader, Select } from '@/Components/UI';

const props = defineProps({
    session: { type: Object, required: true },
    subjects: { type: Array, default: () => [] },
    blueprints: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.session.id);

const form = useForm({
    subject_id: props.session.subject_id,
    blueprint_id: props.session.blueprint_id,
    type: props.session.type || 'weekly',
    title: props.session.title || '',
    date: props.session.date,
    start_time: props.session.start_time || '10:00',
    end_time: props.session.end_time || '13:00',
    duration_min: props.session.duration_min ?? null,
});

const subjectOptions = computed(() => props.subjects.map((s) => ({ value: s.id, label: s.name })));
const blueprintOptions = computed(() =>
    props.blueprints.filter((b) => b.subject_id === form.subject_id)
        .map((b) => ({ value: b.id, label: `${b.name} · ${b.total_questions} savol · ${b.duration_min} daq` })),
);
const typeOptions = [
    { value: 'weekly', label: 'Shanba imtihoni' },
    { value: 'open_regional', label: 'Ochiq viloyat imtihoni' },
];
const selectedBlueprint = computed(() => props.blueprints.find((b) => b.id === form.blueprint_id) || null);

watch(() => form.subject_id, () => {
    if (!blueprintOptions.value.some((o) => o.value === form.blueprint_id)) {
        form.blueprint_id = blueprintOptions.value[0]?.value ?? null;
    }
}, { immediate: !props.session.blueprint_id });

const windowMinutes = computed(() => {
    const [sh, sm] = String(form.start_time || '0:0').split(':').map(Number);
    const [eh, em] = String(form.end_time || '0:0').split(':').map(Number);
    return eh * 60 + em - (sh * 60 + sm);
});
const effectiveDuration = computed(() => form.duration_min || selectedBlueprint.value?.duration_min || null);
const isSaturday = computed(() => (form.date ? new Date(`${form.date}T12:00:00`).getDay() === 6 : true));

const submit = () => {
    const transform = (data) => ({ ...data, duration_min: data.duration_min || null });
    if (isEdit.value) form.transform(transform).put(route('teacher.exam.sessions.update', props.session.id));
    else form.transform(transform).post(route('teacher.exam.sessions.store'));
};
</script>

<template>
    <Head :title="isEdit ? 'Imtihonni tahrirlash' : 'Imtihon rejalashtirish'" />

    <TeacherLayout>
        <PageHeader :title="isEdit ? 'Imtihonni tahrirlash' : 'Imtihon rejalashtirish'"
            :breadcrumbs="[{ label: 'Imtihonlar', href: route('teacher.exam.sessions.index') }, { label: isEdit ? session.title : 'Yangi' }]" />

        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <Card title="Imtihon">
                <div class="space-y-4">
                    <Select v-model="form.subject_id" label="Fan" :options="subjectOptions" required :error="form.errors.subject_id" />
                    <Select v-model="form.blueprint_id" label="Imtihon rejasi" :options="blueprintOptions" placeholder="Tanlang" required
                        :error="form.errors.blueprint_id" hint="Qaysi mavzudan nechta savol tushishini reja belgilaydi" />
                    <Alert v-if="!blueprintOptions.length" variant="warning">
                        Bu fan uchun faol haftalik reja yo'q.
                        <template #actions><Button size="sm" variant="secondary" :href="route('teacher.exam.blueprints.create')">Reja yaratish</Button></template>
                    </Alert>
                    <Select v-model="form.type" label="Turi" :options="typeOptions" required :error="form.errors.type" />
                    <Input v-model="form.title" label="Nomi" required :error="form.errors.title" maxlength="255" />
                </div>
            </Card>

            <Card title="Vaqt" description="Imtihon oynasi ichida o'quvchi istalgan paytda boshlaydi; har kimga davomiylik bo'yicha vaqt beriladi, lekin oyna yopilganda hamma topshiriladi.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <Input v-model="form.date" type="date" label="Sana" required class="sm:col-span-3" :error="form.errors.date"
                        :hint="isSaturday ? null : 'Tanlangan kun shanba emas'" />
                    <Input v-model="form.start_time" type="time" label="Boshlanish" required :error="form.errors.start_time" />
                    <Input v-model="form.end_time" type="time" label="Tugash" required :error="form.errors.end_time" />
                    <Input v-model.number="form.duration_min" type="number" min="5" max="300" label="Davomiylik (daq)" optional
                        :placeholder="selectedBlueprint ? String(selectedBlueprint.duration_min) : ''" :error="form.errors.duration_min" />
                </div>
                <p class="mt-4 flex gap-2 text-13 text-fg-muted">
                    <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    <span>Oyna: <span class="tabular-nums">{{ Math.max(0, windowMinutes) }}</span> daqiqa, har o'quvchiga
                        <span class="tabular-nums">{{ effectiveDuration ?? '—' }}</span> daqiqa.</span>
                </p>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="secondary" :href="isEdit ? route('teacher.exam.sessions.show', session.id) : route('teacher.exam.sessions.index')">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">{{ isEdit ? 'Saqlash' : 'Yaratish' }}</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
