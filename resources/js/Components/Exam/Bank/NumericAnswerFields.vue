<script setup>
/**
 * NumericAnswerFields — expected answer of a numeric / numeric_with_unit question:
 * value, tolerance (abs | rel), canonical unit and accepted alternative units with a factor.
 *   <NumericAnswerFields v-model="form.answer" :with-unit="form.question_type === 'numeric_with_unit'" :errors="form.errors" />
 */
import { computed } from 'vue';
import { PlusIcon, TrashIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { UNIT_PRESETS, unitPresetRows } from './bank';

const props = defineProps({
    modelValue: { type: Object, default: null },
    withUnit: Boolean,
    errors: { type: Object, default: () => ({}) },
    compact: Boolean,
});
const emit = defineEmits(['update:modelValue']);

const answer = computed(() => ({
    value: '', tolerance_type: 'abs', tolerance: '0', unit: '', accepted_units: [], note: '',
    ...(props.modelValue || {}),
}));
const set = (patch) => emit('update:modelValue', { ...answer.value, ...patch });

const units = computed(() => answer.value.accepted_units || []);
const setUnitRow = (i, patch) => set({ accepted_units: units.value.map((r, j) => (j === i ? { ...r, ...patch } : r)) });
const addUnit = () => set({ accepted_units: [...units.value, { unit: '', factor: '' }] });
const removeUnit = (i) => set({ accepted_units: units.value.filter((_, j) => j !== i) });
const presets = computed(() => unitPresetRows(answer.value.unit, units.value));
const addPresets = () => set({ accepted_units: [...units.value, ...presets.value] });
const knownUnits = Object.keys(UNIT_PRESETS);

const err = (k) => props.errors?.[`answer.${k}`] || null;
const unitErr = computed(() => Object.keys(props.errors || {}).find((k) => k.startsWith('answer.accepted_units')));
</script>

<template>
    <div class="space-y-4">
        <div :class="['grid gap-4', withUnit ? 'sm:grid-cols-[minmax(0,1fr)_9rem]' : '']">
            <Input :model-value="answer.value" label="To'g'ri javob (son)" inputmode="decimal" placeholder="Masalan: 20 yoki 0,5"
                :error="err('value')" @update:model-value="set({ value: $event })" />
            <div v-if="withUnit">
                <Input :model-value="answer.unit" label="Birlik (asosiy)" placeholder="m/s" list="exam-si-units" :error="err('unit')"
                    @update:model-value="set({ unit: $event })" />
                <datalist id="exam-si-units"><option v-for="u in knownUnits" :key="u" :value="u" /></datalist>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <Select :model-value="answer.tolerance_type" label="Xatolik turi"
                :options="[{ value: 'abs', label: 'Absolyut (± son)' }, { value: 'rel', label: 'Nisbiy (0,01 = 1%)' }]"
                @update:model-value="set({ tolerance_type: $event })" />
            <Input :model-value="answer.tolerance" label="Ruxsat etilgan xatolik" inputmode="decimal" placeholder="0"
                :error="err('tolerance')" @update:model-value="set({ tolerance: $event })" />
        </div>

        <div v-if="withUnit" class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-13 font-medium text-fg">Qabul qilinadigan boshqa birliklar</p>
                <div class="flex gap-1.5">
                    <Button v-if="presets.length" size="sm" variant="ghost" :icon-left="SparklesIcon" @click="addPresets">
                        {{ answer.unit }} uchun odatiylar
                    </Button>
                    <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addUnit">Birlik</Button>
                </div>
            </div>
            <p class="text-xs text-fg-subtle">Koeffitsiyent — bitta shu birlik nechta asosiy birlikka teng (1 km/soat = 0,2777… m/s).</p>
            <div v-for="(row, i) in units" :key="i" class="flex items-start gap-2">
                <Input :model-value="row.unit" size="sm" placeholder="km/soat" class="w-32" :aria-label="`Birlik ${i + 1}`"
                    @update:model-value="setUnitRow(i, { unit: $event })" />
                <span class="mt-2 text-13 text-fg-subtle">= ×</span>
                <Input :model-value="row.factor" size="sm" inputmode="decimal" placeholder="0.2777777778" class="min-w-0 flex-1"
                    :aria-label="`Koeffitsiyent ${i + 1}`" @update:model-value="setUnitRow(i, { factor: $event })" />
                <span class="mt-2 hidden text-13 text-fg-subtle sm:inline">{{ answer.unit || '—' }}</span>
                <IconButton :icon="TrashIcon" label="Olib tashlash" size="sm" @click="removeUnit(i)" />
            </div>
            <p v-if="unitErr" class="text-13 text-danger">{{ errors[unitErr] }}</p>
        </div>

        <Input v-if="!compact" :model-value="answer.note" label="Izoh" optional placeholder="Masalan: 0,01 gacha yaxlitlang"
            :error="err('note')" @update:model-value="set({ note: $event })" />
    </div>
</template>
