<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    measurements: Array,
    calculations: Array,
    currentTask: Object,
    formulas: Array,
});

const emit = defineEmits(['calculate']);

// Calculator state
const showCalculator = ref(false);
const selectedFormula = ref(null);
const formulaInputs = ref({});
const calculationResult = ref(null);

const selectFormula = (formula) => {
    selectedFormula.value = formula;
    formulaInputs.value = {};
    calculationResult.value = null;
    showCalculator.value = true;
};

const calculate = () => {
    if (!selectedFormula.value) return;

    // Simple evaluation (for demo - in production use proper math parser)
    try {
        const vars = formulaInputs.value;
        let result;

        // Calculate based on formula id
        switch (selectedFormula.value.id) {
            case 'period':
                result = 2 * Math.PI * Math.sqrt(vars.L / vars.g);
                break;
            case 'gravity':
                result = (4 * Math.PI * Math.PI * vars.L) / (vars.T * vars.T);
                break;
            case 'frequency':
                result = 1 / vars.T;
                break;
            case 'height':
                result = 0.5 * vars.g * vars.t * vars.t;
                break;
            case 'velocity':
                result = vars.g * vars.t;
                break;
            case 'gravity_from_h':
                result = (2 * vars.h) / (vars.t * vars.t);
                break;
            case 'ohm':
                result = vars.U / vars.R;
                break;
            case 'resistance':
                result = vars.U / vars.I;
                break;
            default:
                result = 0;
        }

        calculationResult.value = result;

        emit('calculate', {
            formula_id: selectedFormula.value.id,
            inputs: { ...formulaInputs.value },
            result: result,
        });
    } catch (e) {
        console.error('Calculation error:', e);
    }
};

const closeCalculator = () => {
    showCalculator.value = false;
    selectedFormula.value = null;
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
                    <span class="c-formula">{{ c.formula_id }}</span>
                    <span class="c-result">= {{ c.result.toFixed(4) }}</span>
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

                <div class="inputs-grid">
                    <div v-for="v in selectedFormula.variables" :key="v.symbol" class="input-group">
                        <label>{{ v.name }} ({{ v.symbol }})</label>
                        <div class="input-with-unit">
                            <input type="number" step="any" v-model.number="formulaInputs[v.symbol]"
                                :placeholder="v.symbol" />
                            <span class="unit">{{ v.unit }}</span>
                        </div>
                    </div>
                </div>

                <button @click="calculate" class="calculate-btn">
                    Hisoblash
                </button>

                <div v-if="calculationResult !== null" class="result-display">
                    Natija: <strong>{{ calculationResult.toFixed(4) }}</strong>
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
</style>
