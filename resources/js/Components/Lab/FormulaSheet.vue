<script setup>
defineProps({
    formulas: Array,
});

defineEmits(['close']);
</script>

<template>
    <div class="formula-overlay" @click.self="$emit('close')">
        <div class="formula-sheet">
            <button @click="$emit('close')" class="close-btn">×</button>

            <h2>📐 Formulalar</h2>

            <div class="formulas-grid">
                <div v-for="formula in formulas" :key="formula.id" class="formula-card">
                    <h4 class="formula-name">{{ formula.name }}</h4>
                    <div class="formula-latex">{{ formula.latex }}</div>
                    <p class="formula-desc">{{ formula.description }}</p>

                    <div v-if="formula.variables" class="variables">
                        <div v-for="v in formula.variables" :key="v.symbol" class="variable">
                            <span class="symbol">{{ v.symbol }}</span>
                            <span class="name">{{ v.name }}</span>
                            <span class="unit">[{{ v.unit }}]</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.formula-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 2rem;
}

.formula-sheet {
    background: #1e293b;
    border-radius: 20px;
    padding: 2rem;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #334155;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

h2 {
    margin-bottom: 1.5rem;
}

.formulas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.formula-card {
    background: #0f172a;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #334155;
}

.formula-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #3b82f6;
    margin-bottom: 0.75rem;
}

.formula-latex {
    font-family: 'Times New Roman', serif;
    font-size: 1.5rem;
    color: white;
    text-align: center;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 8px;
    margin-bottom: 0.75rem;
}

.formula-desc {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.variables {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.variable {
    display: grid;
    grid-template-columns: 30px 1fr auto;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #64748b;
}

.variable .symbol {
    font-weight: 600;
    color: #f59e0b;
}

.variable .unit {
    color: #475569;
}
</style>
