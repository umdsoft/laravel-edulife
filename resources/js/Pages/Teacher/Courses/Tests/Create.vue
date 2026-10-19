<script setup>
import { computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { DocumentTextIcon, RectangleStackIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Select from '@/Components/UI/Select.vue';
import FormField from '@/Components/UI/FormField.vue';
import Alert from '@/Components/UI/Alert.vue';
import TestSettingsFields from '@/Components/Teacher/TestSettingsFields.vue';

const props = defineProps({
    course: Object,
    modules: Array,
    testTypes: Object,
});

// Curriculum links pass ?type=…&module_id=…&lesson_id=… — preselect them
const query = new URLSearchParams(String(usePage().url || '').split('?')[1] || '');
const TYPES = ['lesson_test', 'module_test', 'final_test'];
const initialType = TYPES.includes(query.get('type')) ? query.get('type') : 'lesson_test';
const initialLesson = query.get('lesson_id') || '';
const initialModule = query.get('module_id')
    || (initialLesson ? (props.modules || []).find((m) => (m.lessons || []).some((l) => String(l.id) === initialLesson))?.id : '')
    || '';

const form = useForm({
    title: '',
    description: '',
    type: initialType,
    module_id: initialModule,
    lesson_id: initialLesson,
    max_attempts: 3,
    shuffle_questions: true,
    shuffle_options: true,
    show_correct_answers: false,
});

const selectedModule = computed(() => {
    return props.modules.find(m => m.id === form.module_id);
});

const availableLessons = computed(() => {
    return selectedModule.value ? selectedModule.value.lessons : [];
});

// A lesson from another module must not stay selected
watch(() => form.module_id, () => {
    if (form.lesson_id && !availableLessons.value.some((l) => l.id === form.lesson_id)) form.lesson_id = '';
});

const typeCards = [
    { value: 'lesson_test', label: 'Dars testi', description: 'Bitta darsdan keyin', icon: DocumentTextIcon },
    { value: 'module_test', label: 'Modul testi', description: 'Modul yakunida', icon: RectangleStackIcon },
    { value: 'final_test', label: 'Yakuniy test', description: 'Butun kurs bo\'yicha', icon: TrophyIcon },
];

const submit = () => {
    form.post(route('teacher.courses.tests.store', props.course.id));
};
</script>

<template>
    <Head title="Yangi test" />

    <TeacherLayout>
        <PageHeader title="Yangi test" description="Testni yarating — keyingi qadamda savollarni qo'shasiz."
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Testlar', href: route('teacher.courses.tests.index', course.id) },
                { label: 'Yangi test' },
            ]" />

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <div class="space-y-6">
                <Card title="Test turi va joylashuvi">
                    <div class="space-y-5">
                        <FormField as="fieldset" label="Test turi" :error="form.errors.type">
                            <div class="grid gap-2 sm:grid-cols-3">
                                <label v-for="t in typeCards" :key="t.value" :class="[
                                    'flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 focus-within:ring-offset-surface',
                                    form.type === t.value ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:bg-surface-2',
                                ]">
                                    <input v-model="form.type" type="radio" name="test-type" :value="t.value" class="sr-only" />
                                    <component :is="t.icon" :class="['mt-px h-5 w-5 shrink-0', form.type === t.value ? 'text-brand' : 'text-fg-subtle']" aria-hidden="true" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-fg">{{ t.label }}</span>
                                        <span class="block text-xs text-fg-subtle">{{ t.description }}</span>
                                        <span v-if="testTypes?.[t.value]" class="mt-1 block text-xs tabular-nums text-fg-muted">
                                            {{ testTypes[t.value].questions_count }} savol · {{ testTypes[t.value].time_limit }} daq · {{ testTypes[t.value].pass_score }}%
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </FormField>

                        <Alert v-if="testTypes?.[form.type]" variant="info">
                            Bu test o'quvchilarga ochilishi uchun kamida {{ testTypes[form.type].questions_count }} ta savol kerak.
                            Vaqt: {{ testTypes[form.type].time_limit }} daqiqa, o'tish bali: {{ testTypes[form.type].pass_score }}%.
                        </Alert>

                        <div v-if="form.type !== 'final_test'" class="grid gap-5 sm:grid-cols-2">
                            <Select v-model="form.module_id" label="Modul" placeholder="Tanlang" :options="modules" value-key="id" label-key="title"
                                required :error="form.errors.module_id" />
                            <Select v-if="form.type === 'lesson_test'" v-model="form.lesson_id" label="Dars" placeholder="Tanlang"
                                :options="availableLessons" value-key="id" label-key="title" required :disabled="!form.module_id"
                                :hint="!form.module_id ? 'Avval modulni tanlang' : (!availableLessons.length ? 'Bu modulda dars yo\'q' : null)"
                                :error="form.errors.lesson_id" />
                        </div>

                        <Alert v-if="!modules.length && form.type !== 'final_test'" variant="warning">
                            Kursda hali modul yo'q. Dars yoki modul testi uchun avval darslar rejasini tuzing.
                        </Alert>
                    </div>
                </Card>

                <TestSettingsFields :form="form" />
            </div>

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                <Button variant="secondary" :href="route('teacher.courses.tests.index', course.id)">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Yaratish va savollarga o'tish</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
