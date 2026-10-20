<script setup>
/**
 * QuestionFormFields — question type, text, points, difficulty, answers, explanation + a live
 * preview with the LaTeX formulas rendered the way the student sees them.
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
import MathText from '@/Components/MathText.vue';
import SingleChoice from './QuestionTypes/SingleChoice.vue';
import MultipleChoice from './QuestionTypes/MultipleChoice.vue';
import TrueFalse from './QuestionTypes/TrueFalse.vue';
import FillBlank from './QuestionTypes/FillBlank.vue';
import Matching from './QuestionTypes/Matching.vue';
import Ordering from './QuestionTypes/Ordering.vue';
import { QUESTION_TYPES, QUESTION_DIFFICULTIES } from './format';
import { bandLabel, calibrationText } from '@/Utils/difficulty';
import { hasMath } from '@/Utils/mathText';

const props = defineProps({
    form: { type: Object, required: true },
    questionTypes: { type: Object, default: () => ({}) },
    typeLocked: Boolean,
    // battle p̂ once there is enough data (DifficultyCalibration) — a hint, never applied automatically
    calibration: { type: Object, default: null },
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

// ---- Preview (what the student sees) ---------------------------------------------
const filledOptions = computed(() => (props.form.options || []).filter((o) => String(o.option_text || '').trim() !== ''));
const previewOptions = computed(() => {
    switch (props.form.type) {
        case 'single_choice':
        case 'multiple_choice':
        case 'true_false':
            return filledOptions.value.map((o) => ({ text: o.option_text, correct: !!o.is_correct }));
        case 'matching':
            return filledOptions.value.map((o) => ({ text: `${o.option_text}  →  ${o.match_text || '…'}`, correct: false }));
        case 'ordering':
            return [...filledOptions.value].sort((a, b) => (a.correct_position || 0) - (b.correct_position || 0))
                .map((o, i) => ({ text: `${i + 1}. ${o.option_text}`, correct: false }));
        case 'fill_blank':
            return filledOptions.value.map((o) => ({ text: o.option_text, correct: true }));
        default:
            return [];
    }
});
const usesMath = computed(() => [props.form.question_text, props.form.explanation, ...filledOptions.value.map((o) => `${o.option_text} ${o.match_text || ''}`)]
    .some((t) => hasMath(t)));

const FORMULA_EXAMPLE = '$v = \\frac{s}{t}$';

const previewDescription = computed(() => (usesMath.value
    ? "Formulalar o'quvchiga xuddi shunday ko'rinadi."
    : "O'quvchi savolni shunday ko'radi."));

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

                <p class="-mt-3 text-13 text-fg-subtle">
                    Formula: <code class="rounded-sm bg-surface-2 px-1 font-mono text-xs text-fg">{{ FORMULA_EXAMPLE }}</code>,
                    alohida qatorda: <code class="rounded-sm bg-surface-2 px-1 font-mono text-xs text-fg">$$…$$</code>
                </p>

                <div class="grid gap-5 sm:grid-cols-[10rem_minmax(0,1fr)]">
                    <Input v-model.number="form.points" label="Ball" type="number" min="1" inputmode="numeric" required
                        :error="form.errors.points" />
                    <FormField as="fieldset" label="Qiyinlik" required :error="form.errors.difficulty"
                        :hint="calibration ? `${calibrationText(calibration)}${calibration.mismatch ? ` — amalda ${bandLabel(calibration.band).toLowerCase()}` : ''}`
                            : (form.difficulty ? null : 'Tanlang — battle va bankdan olishda shu daraja ishlatiladi')">
                        <div class="inline-flex h-10 rounded-md border border-border-strong bg-surface p-0.5" role="radiogroup" aria-label="Qiyinlik">
                            <label v-for="d in QUESTION_DIFFICULTIES" :key="d.value" :class="[
                                'flex cursor-pointer items-center rounded-sm px-3.5 text-13 font-medium transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500',
                                form.difficulty === d.value ? 'bg-brand-50 text-brand' : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                            ]">
                                <input v-model="form.difficulty" type="radio" name="question-difficulty" :value="d.value" class="sr-only" />
                                {{ d.label }}
                            </label>
                        </div>
                    </FormField>
                </div>
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

        <Card title="Ko'rinishi" :description="previewDescription">
            <div class="space-y-3" aria-live="polite">
                <MathText v-if="form.question_text" :text="form.question_text" tag="p" class="block text-[15px] font-medium leading-relaxed text-fg" />
                <p v-else class="text-13 text-fg-subtle">Savol matnini yozing.</p>
                <ul v-if="previewOptions.length" class="space-y-1.5">
                    <li v-for="(o, i) in previewOptions" :key="i" :class="[
                        'flex items-start gap-2 rounded-md border px-3 py-2 text-sm',
                        o.correct ? 'border-success-100 bg-success-50 text-fg' : 'border-border text-fg',
                    ]">
                        <MathText :text="o.text" class="min-w-0 flex-1" />
                        <span v-if="o.correct" class="shrink-0 text-xs font-medium text-success">To'g'ri</span>
                    </li>
                </ul>
                <div v-if="form.explanation" class="rounded-md bg-info-50 p-3 text-sm text-fg-muted">
                    <span class="font-medium text-fg">Izoh: </span><MathText :text="form.explanation" />
                </div>
            </div>
        </Card>
    </div>
</template>
