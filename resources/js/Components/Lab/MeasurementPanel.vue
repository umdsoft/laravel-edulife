<script setup>
import { ref, computed } from 'vue';
import { CalculatorIcon, ScaleIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/20/solid';
import Alert from '@/Components/UI/Alert.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Modal from '@/Components/UI/Modal.vue';
import { calculate as runCalculator, calculatorInputs, calculatorResult, hasCalculator, formatResult } from './physics/calculator.js';

const props = defineProps({
    measurements: { type: Array, default: () => [] },
    calculations: { type: Array, default: () => [] },
    currentTask: Object,
    formulas: { type: Array, default: () => [] },
});

const emit = defineEmits(['calculate']);

// Calculator state
const showCalculator = ref(false);
const selectedFormula = ref(null);
const formulaInputs = ref({});
const calculationResult = ref(null); // {ok, value, unit, symbol, note} | {ok:false, error}

// Input fields come from the calculator definition (authoritative symbols and units);
// the human-readable name is taken from the seeded formula's variables.
const inputFields = computed(() => {
    const f = selectedFormula.value;
    if (!f) return [];
    const vars = Array.isArray(f.variables) ? f.variables : [];
    return calculatorInputs(f.id).map((spec) => ({
        symbol: spec.symbol,
        unit: spec.unit,
        name: vars.find((v) => v.symbol === spec.symbol)?.name || spec.symbol,
    }));
});

const canCalculate = computed(() => !!selectedFormula.value && hasCalculator(selectedFormula.value.id));
const resultInfo = computed(() => (selectedFormula.value ? calculatorResult(selectedFormula.value.id) : null));

// Newest first, so the latest reading is always visible
const measurementsDesc = computed(() => props.measurements.map((m, i) => ({ ...m, _i: i })).reverse());
const calculationsDesc = computed(() => props.calculations.map((c, i) => ({ ...c, _i: i })).reverse());
const formulaName = (id) => props.formulas.find((f) => f.id === id)?.name || id;

const selectFormula = (formula) => {
    selectedFormula.value = formula;
    formulaInputs.value = {};
    calculationResult.value = null;
    showCalculator.value = true;
};

const calculate = () => {
    if (!selectedFormula.value) return;

    const res = runCalculator(selectedFormula.value.id, formulaInputs.value);
    calculationResult.value = res;
    if (!res.ok) return;

    emit('calculate', {
        formula_id: selectedFormula.value.id,
        inputs: { ...formulaInputs.value },
        result: res.value,
        unit: res.unit,
    });
};

const closeCalculator = () => {
    showCalculator.value = false;
    selectedFormula.value = null;
    calculationResult.value = null;
};
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Measurements -->
        <section aria-labelledby="lab-measurements-title">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 id="lab-measurements-title" class="text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                    O'lchov natijalari
                </h2>
                <span v-if="measurements.length" class="text-xs text-fg-subtle tabular-nums">{{ measurements.length }} ta</span>
            </div>

            <div v-if="measurements.length === 0"
                class="flex items-center gap-3 rounded-md border border-dashed border-border-strong px-3 py-4 text-13 text-fg-subtle">
                <ScaleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span>Hali o'lchov yo'q. Simulyatsiyada o'lchovni bajaring.</span>
            </div>

            <dl v-else class="max-h-56 divide-y divide-border overflow-y-auto rounded-md border border-border bg-surface text-13"
                aria-live="polite">
                <div v-for="m in measurementsDesc" :key="m._i" class="flex items-baseline justify-between gap-3 px-3 py-2">
                    <dt class="min-w-0 truncate text-fg-muted">{{ m.name }}</dt>
                    <dd class="shrink-0 font-semibold text-fg tabular-nums">{{ m.value }} <span class="font-normal text-fg-subtle">{{ m.unit }}</span></dd>
                </div>
            </dl>
        </section>

        <!-- Calculations -->
        <section v-if="calculations.length > 0" aria-labelledby="lab-calculations-title">
            <h2 id="lab-calculations-title" class="mb-2 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                Hisob-kitoblar
            </h2>
            <dl class="max-h-44 divide-y divide-border overflow-y-auto rounded-md border border-border bg-surface text-13">
                <div v-for="c in calculationsDesc" :key="c._i" class="flex items-baseline justify-between gap-3 px-3 py-2">
                    <dt class="min-w-0 truncate text-fg-muted">{{ formulaName(c.formula_id) }}</dt>
                    <dd class="shrink-0 font-semibold text-brand tabular-nums">= {{ formatResult(Number(c.result)) }} <span class="font-normal text-fg-subtle">{{ c.unit || '' }}</span></dd>
                </div>
            </dl>
        </section>

        <!-- Formula calculator -->
        <section aria-labelledby="lab-calculator-title">
            <h2 id="lab-calculator-title" class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-fg-subtle">
                <CalculatorIcon class="h-4 w-4" aria-hidden="true" />
                Kalkulyator
            </h2>

            <p v-if="!formulas.length" class="text-13 text-fg-subtle">Bu tajriba uchun formulalar yo'q.</p>
            <div v-else class="flex flex-wrap gap-2">
                <button v-for="f in formulas" :key="f.id" type="button"
                    class="focus-ring inline-flex min-h-[36px] items-center rounded-md border border-border-strong bg-surface px-3 py-1.5 text-left text-13 font-medium text-fg-muted shadow-xs transition-colors duration-150 hover:border-brand-200 hover:bg-brand-50 hover:text-brand"
                    @click="selectFormula(f)">
                    {{ f.name }}
                </button>
            </div>
        </section>

        <!-- Calculator dialog -->
        <Modal :show="showCalculator && !!selectedFormula" :title="selectedFormula?.name || 'Kalkulyator'"
            :description="selectedFormula?.description || null" size="md" @close="closeCalculator">
            <template v-if="selectedFormula">
                <div class="mb-4 rounded-md bg-surface-2 px-4 py-3 text-center font-serif text-xl text-fg">
                    {{ selectedFormula.latex }}
                </div>

                <form v-if="canCalculate" class="space-y-4 pb-2" @submit.prevent="calculate">
                    <div class="grid gap-3">
                        <Input v-for="(v, idx) in inputFields" :key="v.symbol" v-model.number="formulaInputs[v.symbol]"
                            type="number" step="any" inputmode="decimal"
                            :label="`${v.name} (${v.symbol})${v.unit ? `, ${v.unit}` : ''}`"
                            :placeholder="v.unit ? `${v.symbol}, ${v.unit}` : v.symbol" :autofocus="idx === 0 || undefined" />
                    </div>

                    <Button type="submit" block :icon-left="CalculatorIcon">Hisoblash</Button>

                    <div v-if="calculationResult && calculationResult.ok" role="status" aria-live="polite"
                        class="rounded-md bg-success-50 px-4 py-3 text-center ring-1 ring-inset ring-success-100">
                        <p class="flex items-center justify-center gap-1.5 text-13 text-success">
                            <CheckCircleIcon class="h-4 w-4" aria-hidden="true" />
                            Natija
                        </p>
                        <p class="mt-1 text-fg">
                            <span class="font-serif">{{ resultInfo?.symbol }}</span> =
                            <strong class="text-lg font-semibold tabular-nums">{{ formatResult(calculationResult.value) }}</strong>
                            {{ calculationResult.unit }}
                        </p>
                        <p v-if="calculationResult.note" class="mt-2 flex items-start justify-center gap-1.5 text-xs text-warning">
                            <ExclamationTriangleIcon class="mt-px h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                            {{ calculationResult.note }}
                        </p>
                    </div>
                    <Alert v-else-if="calculationResult" variant="danger">{{ calculationResult.error }}</Alert>
                </form>

                <Alert v-else variant="warning" class="mb-2">
                    Bu formula uchun kalkulyator mavjud emas. Formulani qo'lda hisoblang.
                </Alert>
            </template>
        </Modal>
    </div>
</template>
