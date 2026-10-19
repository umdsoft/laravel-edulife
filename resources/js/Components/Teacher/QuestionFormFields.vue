<script setup>
/**
 * QuestionFormFields — question type, text, points, answers, explanation.
 * Shared by Tests/Questions/Create and Edit; binds to the page's useForm() (field names unchanged).
 *   <QuestionFormFields :form="form" :question-types="questionTypes" :type-locked="true" />
 */
import { computed } from 'vue';
import {
    CheckCircleIcon, ListBulletIcon, ScaleIcon, PencilIcon, ArrowsRightLeftIcon, BarsArrowDownIcon, LockClosedIcon,
} from '@heroicons/vue/24/outline';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormField from '@/Components/UI/FormField.vue';
import Alert from '@/Components/UI/Alert.vue';
import SingleChoice from './QuestionTypes/SingleChoice.vue';
import MultipleChoice from './QuestionTypes/MultipleChoice.vue';
import TrueFalse from './QuestionTypes/TrueFalse.vue';
import FillBlank from './QuestionTypes/FillBlank.vue';
import Matching from './QuestionTypes/Matching.vue';
import Ordering from './QuestionTypes/Ordering.vue';
import { QUESTION_TYPES } from './format';

const props = defineProps({
    form: { type: Object, required: true },
    questionTypes: { type: Object, default: () => ({}) },
    typeLocked: Boolean,
});

const ICONS = {
    single_choice: CheckCircleIcon,
    multiple_choice: ListBulletIcon,
    true_false: ScaleIcon,
    fill_blank: PencilIcon,
    matching: ArrowsRightLeftIcon,
    ordering: BarsArrowDownIcon,
};

const types = computed(() => {
    const source = Object.keys(props.questionTypes || {}).length ? props.questionTypes : QUESTION_TYPES;
    return Object.entries(source).map(([value, label]) => ({ value, label, icon: ICONS[value] || CheckCircleIcon }));
});
const currentLabel = computed(() => types.value.find((t) => t.value === props.form.type)?.label || props.form.type);

// Errors for options.* arrive as "options.0.option_text" etc. — show the first one above the editor
const optionsError = computed(() => {
    const e = props.form.errors || {};
    if (e.options) return e.options;
    const key = Object.keys(e).find((k) => k.startsWith('options.'));
    return key ? e[key] : null;
});
</script>

<template>
    <div class="space-y-6">
        <Card title="Savol">
            <div class="space-y-5">
                <FormField v-if="!typeLocked" as="fieldset" label="Savol turi" :error="form.errors.type">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label v-for="t in types" :key="t.value" :class="[
                            'flex cursor-pointer items-center gap-2.5 rounded-md border px-3 py-2.5 transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 focus-within:ring-offset-surface',
                            form.type === t.value ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:bg-surface-2',
                        ]">
                            <input v-model="form.type" type="radio" name="question-type" :value="t.value" class="sr-only" />
                            <component :is="t.icon" :class="['h-5 w-5 shrink-0', form.type === t.value ? 'text-brand' : 'text-fg-subtle']" aria-hidden="true" />
                            <span class="min-w-0 text-13 font-medium leading-tight text-fg">{{ t.label }}</span>
                        </label>
                    </div>
                </FormField>
                <div v-else class="flex items-center gap-2 rounded-md bg-surface-2 px-3 py-2 text-sm">
                    <LockClosedIcon class="h-4 w-4 shrink-0 text-fg-subtle" aria-hidden="true" />
                    <span class="text-fg-muted">Savol turi:</span>
                    <span class="font-medium text-fg">{{ currentLabel }}</span>
                    <span class="ml-auto hidden text-13 text-fg-subtle sm:inline">o'zgartirib bo'lmaydi</span>
                </div>

                <Textarea v-model="form.question_text" label="Savol matni" required :rows="3" autoresize
                    placeholder="Masalan: Jism 2 s da 10 m yo'l bosdi. Uning o'rtacha tezligi qancha?" :error="form.errors.question_text" />

                <Input v-model.number="form.points" label="Ball" type="number" min="1" inputmode="numeric" required class="sm:max-w-[10rem]"
                    :error="form.errors.points" />
            </div>
        </Card>

        <Card title="Javoblar">
            <Alert v-if="optionsError" variant="danger" class="mb-4">{{ optionsError }}</Alert>
            <SingleChoice v-if="form.type === 'single_choice'" v-model="form.options" />
            <MultipleChoice v-if="form.type === 'multiple_choice'" v-model="form.options" />
            <TrueFalse v-if="form.type === 'true_false'" v-model="form.options" />
            <FillBlank v-if="form.type === 'fill_blank'" v-model="form.options" />
            <Matching v-if="form.type === 'matching'" v-model="form.options" />
            <Ordering v-if="form.type === 'ordering'" v-model="form.options" />
        </Card>

        <Card title="Izoh" description="Test tugagach o'quvchi ko'radigan yechim yoki tushuntirish.">
            <Textarea v-model="form.explanation" label="Izoh" optional :rows="3" autoresize placeholder="To'g'ri javob uchun izoh…"
                :error="form.errors.explanation" />
        </Card>
    </div>
</template>
