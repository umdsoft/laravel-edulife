<script setup>
import { ref, computed } from 'vue';
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
    <div class="measurement-panel">
        <h3 class="panel-title">O'lchov natijalari</h3>

        <!-- Measurements List -->
        <div class="measurements-list">
            <div v-if="measurements.length === 0" class="empty-state">
                Hali o'lchov yo'q
            </div>

            <div v-for="(m, i) in measurements" :key="i" class="measurement-item">
                <span class="m-name">{{ m.name }}</span>
                <span class="m-value">{{ m.value }} {{ m.unit }}</span>
            </div>
        </div>

        <!-- Calculations List -->
        <div v-if="calculations.length > 0" class="calculations-section">
            <h4 class="section-title">Hisob-kitoblar</h4>
            <div class="calculations-list">
                <div v-for="(c, i) in calculations" :key="i" class="calculation-item">
                    <span class="c-formula">{{ formulas.find((f) => f.id === c.formula_id)?.name || c.formula_id }}</span>
                    <span class="c-result">= {{ formatResult(Number(c.result)) }} {{ c.unit || '' }}</span>
                </div>
            </div>
        </div>

        <!-- Formula Calculator -->
        <div class="calculator-section">
            <h4 class="section-title">🧮 Kalkulyator</h4>

            <div class="formula-buttons">
                <button v-for="f in formulas" :key="f.id" @click="selectFormula(f)" class="formula-btn">
                    {{ f.name }}
                </button>
            </div>
        </div>

        <!-- Calculator Modal -->
        <div v-if="showCalculator" class="calculator-modal">
            <div class="calculator-content">
                <button @click="closeCalculator" class="close-btn">×</button>

                <h4>{{ selectedFormula.name }}</h4>
                <div class="formula-display">{{ selectedFormula.latex }}</div>
                <p class="formula-desc">{{ selectedFormula.description }}</p>

                <template v-if="canCalculate">
                    <div class="inputs-grid">
                        <div v-for="v in inputFields" :key="v.symbol" class="input-group">
                            <label>{{ v.name }} ({{ v.symbol }})</label>
                            <div class="input-with-unit">
                                <input type="number" step="any" v-model.number="formulaInputs[v.symbol]"
                                    :placeholder="v.symbol" @keyup.enter="calculate" />
                                <span v-if="v.unit" class="unit">{{ v.unit }}</span>
                            </div>
                        </div>
                    </div>

                    <button @click="calculate" class="calculate-btn">
                        Hisoblash
                    </button>

                    <div v-if="calculationResult && calculationResult.ok" class="result-display">
                        Natija: {{ resultInfo?.symbol }} =
                        <strong>{{ formatResult(calculationResult.value) }}</strong> {{ calculationResult.unit }}
                        <p v-if="calculationResult.note" class="result-note">{{ calculationResult.note }}</p>
                    </div>
                    <div v-else-if="calculationResult" class="result-error">
                        {{ calculationResult.error }}
                    </div>
                </template>
                <div v-else class="result-error">
                    Bu formula uchun kalkulyator mavjud emas. Formulani qo'lda hisoblang.
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.measurement-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.panel-title,
.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 0.8rem;
    margin-top: 1.5rem;
}

.measurements-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
}

.empty-state {
    color: #64748b;
    font-size: 0.85rem;
    text-align: center;
    padding: 1rem;
}

.measurement-item {
    display: flex;
    justify-content: space-between;
    padding: 0.625rem 0.75rem;
    background: #0f172a;
    border-radius: 8px;
    font-size: 0.85rem;
}

.m-name {
    color: #94a3b8;
}

.m-value {
    color: #10b981;
    font-weight: 600;
}

.calculations-list {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.calculation-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: #0f172a;
    border-radius: 6px;
    font-size: 0.8rem;
}

.c-formula {
    color: #64748b;
}

.c-result {
    color: #3b82f6;
    font-weight: 600;
}

.formula-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.formula-btn {
    padding: 0.5rem 0.75rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: #94a3b8;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}

.formula-btn:hover {
    border-color: #3b82f6;
    color: white;
}

.calculator-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.calculator-content {
    background: #1e293b;
    border-radius: 16px;
    padding: 1.5rem;
    width: 90%;
    max-width: 400px;
    position: relative;
}

.close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #334155;
    border: none;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
}

.calculator-content h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.formula-display {
    font-family: 'Times New Roman', serif;
    font-size: 1.25rem;
    color: #3b82f6;
    padding: 0.75rem;
    background: #0f172a;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 0.5rem;
}

.formula-desc {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.inputs-grid {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.input-group label {
    display: block;
    font-size: 0.8rem;
    color: #94a3b8;
    margin-bottom: 0.25rem;
}

.input-with-unit {
    display: flex;
    gap: 0.5rem;
}

.input-with-unit input {
    flex: 1;
    padding: 0.5rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 6px;
    color: white;
    font-size: 0.9rem;
}

.input-with-unit .unit {
    padding: 0.5rem;
    background: #334155;
    border-radius: 6px;
    font-size: 0.8rem;
    color: #94a3b8;
}

.calculate-btn {
    width: 100%;
    padding: 0.75rem;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.result-display {
    margin-top: 1rem;
    padding: 1rem;
    background: #0f172a;
    border-radius: 8px;
    text-align: center;
    font-size: 1rem;
}

.result-display strong {
    color: #10b981;
    font-size: 1.25rem;
}

.result-note {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #f59e0b;
}

.result-error {
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.4);
    border-radius: 8px;
    color: #fca5a5;
    font-size: 0.85rem;
    text-align: center;
}
</style>
