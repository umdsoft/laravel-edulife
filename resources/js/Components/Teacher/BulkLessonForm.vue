<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Alert from '@/Components/UI/Alert.vue';

const props = defineProps({
    course: Object,
    module: Object,
});

const emit = defineEmits(['cancel']);

const form = useForm({
    lessons: [
        { title: '', description: '', type: 'video', is_free: false, is_preview: false }
    ],
});

const types = [
    { value: 'video', label: 'Video dars' },
    { value: 'text', label: 'Matnli dars' },
    { value: 'quiz', label: 'Test sinovi' },
];

const titleInputs = ref([]);

const focusTitle = (index) => nextTick(() => titleInputs.value[index]?.focus());

const addLesson = () => {
    form.lessons.push({
        title: '',
        description: '',
        type: 'video',
        is_free: false,
        is_preview: false,
    });
    focusTitle(form.lessons.length - 1);
};

const removeLesson = (index) => {
    if (form.lessons.length > 1) {
        form.lessons.splice(index, 1);
    }
};

// Enter in a title jumps to the next lesson (adds one at the end) — fast keyboard entry
const onTitleEnter = (index) => {
    if (index === form.lessons.length - 1) addLesson();
    else focusTitle(index + 1);
};

const submit = () => {
    form.post(route('teacher.courses.modules.bulk-lessons.store', [props.course.id, props.module.id]));
};
</script>

<template>
    <form novalidate @submit.prevent="submit">
        <Alert v-if="form.errors.lessons" variant="danger" class="mb-4">{{ form.errors.lessons }}</Alert>

        <ol class="space-y-3">
            <li v-for="(lesson, index) in form.lessons" :key="index" class="rounded-lg border border-border bg-surface p-4 shadow-xs sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-fg">
                        <span class="tabular-nums text-fg-subtle">{{ index + 1 }}.</span> Dars
                    </h3>
                    <IconButton v-if="form.lessons.length > 1" :icon="TrashIcon" :label="`${index + 1}-darsni olib tashlash`"
                        variant="danger" size="sm" @click="removeLesson(index)" />
                </div>

                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem]">
                    <Input :ref="(el) => (titleInputs[index] = el)" v-model="lesson.title" label="Dars nomi" required
                        placeholder="Dars nomini kiriting" maxlength="255" :error="form.errors[`lessons.${index}.title`]"
                        @keydown.enter.prevent="onTitleEnter(index)" />
                    <Select v-model="lesson.type" label="Turi" :options="types" :error="form.errors[`lessons.${index}.type`]" />
                </div>

                <Textarea v-model="lesson.description" label="Qisqacha tavsif" optional :rows="2" class="mt-4"
                    placeholder="Dars haqida qisqacha ma'lumot" :error="form.errors[`lessons.${index}.description`]" />

                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-3">
                    <Checkbox v-model="lesson.is_free" label="Bepul dars" />
                    <Checkbox v-model="lesson.is_preview" label="Ochiq ko'rish (preview)" />
                </div>
            </li>
        </ol>

        <button type="button"
            class="focus-ring mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-dashed border-border-strong text-sm font-medium text-fg-muted transition-colors hover:border-brand-500 hover:bg-surface hover:text-brand"
            @click="addLesson">
            <PlusIcon class="h-5 w-5" aria-hidden="true" />
            Yana dars qo'shish
        </button>

        <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
            <span class="mr-auto text-13 tabular-nums text-fg-subtle">{{ form.lessons.length }} ta dars</span>
            <Button variant="secondary" @click="emit('cancel')">Bekor qilish</Button>
            <Button type="submit" :loading="form.processing">Saqlash</Button>
        </div>
    </form>
</template>
