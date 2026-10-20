<script setup>
/**
 * QuestionForm — fields of an exam question (bank create / edit). Binds to the page's useForm():
 *   subject_id, topic_id, question_type, question_text, difficulty_level, exam_formats, source_ref,
 *   options [{text, is_correct}] | answer {value, tolerance_type, tolerance, unit, accepted_units, note},
 *   units_note, constants [{key, value, unit}], solution_text, solution_video_url.
 * Text is LaTeX-Markdown ($…$, $$…$$) and is previewed with the same renderer students see.
 */
import { computed, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import FormField from '@/Components/UI/FormField.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Alert from '@/Components/UI/Alert.vue';
import MarkdownEditor from '@/Components/Teacher/MarkdownEditor.vue';
import OptionsEditor from './OptionsEditor.vue';
import NumericAnswerFields from './NumericAnswerFields.vue';
import { DIFFICULTIES, FORMATS, TYPES, isChoiceType, topicOptions } from './bank';
import { bandLabel, calibrationText, levelLabel } from '@/Utils/difficulty';

const props = defineProps({
    form: { type: Object, required: true },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    // observed solve rate (DifficultyCalibration) — a hint next to the level, never applied automatically
    calibration: { type: Object, default: null },
});

const f = props.form;
const subjectOptions = computed(() => props.subjects.map((s) => ({ value: s.id, label: s.name_uz })));
const subjectCode = computed(() => props.subjects.find((s) => s.id === f.subject_id)?.code);
const topicChoices = computed(() => topicOptions(props.topics, f.subject_id, { keep: f.topic_id }));
const typeChoices = computed(() => Object.entries(TYPES)
    .filter(([v]) => v !== 'numeric_with_unit' || subjectCode.value !== 'math' || f.question_type === v)
    .map(([value, label]) => ({ value, label })));

// a topic belongs to one subject
watch(() => f.subject_id, () => {
    if (f.topic_id && !props.topics.some((t) => t.id === f.topic_id && t.subject_id === f.subject_id)) f.topic_id = null;
});

// each type has its own answer shape
const blankOptions = () => Array.from({ length: 4 }, () => ({ text: '', is_correct: null }));
const blankAnswer = () => ({ value: '', tolerance_type: 'abs', tolerance: '0', unit: '', accepted_units: [], note: '' });
watch(() => f.question_type, (type) => {
    if (isChoiceType(type) && !(f.options || []).length) f.options = blankOptions();
    if (!isChoiceType(type) && !f.answer) f.answer = blankAnswer();
    if (type === 'single_choice' && (f.options || []).filter((o) => o.is_correct === true).length > 1) {
        f.options = f.options.map((o) => ({ ...o, is_correct: null }));
    }
}, { immediate: true });

const toggleFormat = (value, on) => {
    const set = new Set(f.exam_formats || []);
    on ? set.add(value) : set.delete(value);
    f.exam_formats = [...set];
};

const addConstant = () => (f.constants = [...(f.constants || []), { key: 'g', value: '10', unit: 'm/s²' }]);
const removeConstant = (i) => (f.constants = f.constants.filter((_, j) => j !== i));
const setConstant = (i, patch) => (f.constants = f.constants.map((c, j) => (j === i ? { ...c, ...patch } : c)));

const difficultyHint = computed(() => {
    const c = props.calibration;
    if (!c) return null;
    const text = calibrationText(c);
    return c.mismatch ? `${text} — amalda ${bandLabel(c.band).toLowerCase()}, taklif: ${levelLabel(c.level)}` : text;
});

const optionsError = computed(() => f.errors.options || null);
const answerError = computed(() => f.errors.answer || null);
</script>

<template>
    <div class="space-y-6">
        <Card title="Tasnif" description="Fan, mavzu, tur va qiyinlik — imtihon rejasi shular bo'yicha savol tanlaydi.">
            <div class="grid gap-5 sm:grid-cols-2">
                <Select v-model="f.subject_id" label="Fan" required :options="subjectOptions" :error="f.errors.subject_id" />
                <Select v-model="f.topic_id" label="Mavzu" placeholder="Mavzuni tanlang" :options="topicChoices" :error="f.errors.topic_id"
                    hint="Tasdiqlash uchun mavzu shart." />
                <Select v-model="f.question_type" label="Savol turi" required :options="typeChoices" :error="f.errors.question_type" />
                <Select v-model="f.difficulty_level" label="Qiyinlik" placeholder="Tanlang" required :options="DIFFICULTIES" :error="f.errors.difficulty_level"
                    :hint="difficultyHint" />
            </div>
            <FormField as="fieldset" label="Imtihon formatlari" optional class="mt-5" :error="f.errors.exam_formats">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <Checkbox v-for="(label, value) in FORMATS" :key="value" :model-value="(f.exam_formats || []).includes(value)"
                        :label="label" @update:model-value="toggleFormat(value, $event)" />
                </div>
            </FormField>
            <Input v-model="f.source_ref" label="Manba" optional class="mt-5" placeholder="To'plam, bet, masala raqami"
                :error="f.errors.source_ref" />
        </Card>

        <Card title="Savol sharti">
            <MarkdownEditor v-model="f.question_text" label="Shart" :rows="6" :error="f.errors.question_text"
                placeholder="Masalan: $x^2 - 5x + 6 = 0$ tenglamaning ildizlari yig'indisini toping." />
            <div v-if="f.question_type === 'numeric_with_unit' || subjectCode === 'physics'" class="mt-5 space-y-4">
                <Input v-model="f.units_note" label="Birliklar haqida izoh" optional placeholder="Masalan: g = 10 m/s² deb oling, javobni m/s da yozing"
                    :error="f.errors.units_note" />
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <p class="text-13 font-medium text-fg">Doimiylar <span class="font-normal text-fg-subtle">(shartda ham yozing)</span></p>
                        <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addConstant">Doimiy</Button>
                    </div>
                    <div v-for="(c, i) in f.constants || []" :key="i" class="flex items-start gap-2">
                        <Input :model-value="c.key" size="sm" class="w-20" placeholder="g" :aria-label="`Doimiy ${i + 1} nomi`"
                            @update:model-value="setConstant(i, { key: $event })" />
                        <span class="mt-2 text-13 text-fg-subtle">=</span>
                        <Input :model-value="c.value" size="sm" class="w-28" inputmode="decimal" placeholder="10" :aria-label="`Doimiy ${i + 1} qiymati`"
                            @update:model-value="setConstant(i, { value: $event })" />
                        <Input :model-value="c.unit" size="sm" class="w-28" placeholder="m/s²" :aria-label="`Doimiy ${i + 1} birligi`"
                            @update:model-value="setConstant(i, { unit: $event })" />
                        <IconButton :icon="TrashIcon" label="Olib tashlash" size="sm" @click="removeConstant(i)" />
                    </div>
                </div>
            </div>
        </Card>

        <Card :title="isChoiceType(f.question_type) ? 'Variantlar' : 'Javob'">
            <Alert v-if="optionsError || answerError" variant="danger" class="mb-4">{{ optionsError || answerError }}</Alert>
            <OptionsEditor v-if="isChoiceType(f.question_type)" v-model="f.options" :multiple="f.question_type === 'multiple_choice'"
                :errors="f.errors" />
            <NumericAnswerFields v-else v-model="f.answer" :with-unit="f.question_type === 'numeric_with_unit'" :errors="f.errors" />
        </Card>

        <Card title="Yechim" description="Ixtiyoriy. O'quvchi natijada xato qilgan masalasining yechimini ko'radi.">
            <div class="space-y-5">
                <MarkdownEditor v-model="f.solution_text" label="Yechim matni" :rows="5" :error="f.errors.solution_text"
                    placeholder="Viyet teoremasi bo'yicha: $x_1 + x_2 = 5$" />
                <Input v-model="f.solution_video_url" label="Yechim videosi" type="url" optional placeholder="https://youtu.be/…"
                    :error="f.errors.solution_video_url" />
            </div>
        </Card>
    </div>
</template>
