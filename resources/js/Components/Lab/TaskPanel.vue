<script setup>
defineProps({
    tasks: Array,
    currentTask: Number,
    tasksProgress: Object,
});

defineEmits(['selectTask']);

const getTaskStatus = (task, progress) => {
    if (!progress[task.step]) return 'pending';
    if (progress[task.step].completed) return 'completed';
    return 'in_progress';
};

const getTaskIcon = (type) => {
    const icons = {
        instruction: '📖',
        measurement: '📏',
        calculation: '🧮',
        conclusion: '📝',
        graph_analysis: '📊',
        circuit_build: '🔌',
    };
    return icons[type] || '📋';
};
</script>

<template>
    <div class="task-panel">
        <h3 class="panel-title">Vazifalar</h3>

        <div class="tasks-list">
            <button v-for="task in tasks" :key="task.step" class="task-item" :class="{
                active: currentTask === task.step,
                completed: getTaskStatus(task, tasksProgress) === 'completed',
                pending: getTaskStatus(task, tasksProgress) === 'pending',
            }" @click="$emit('selectTask', task.step)">
                <span class="task-number">
                    <template v-if="getTaskStatus(task, tasksProgress) === 'completed'">✓</template>
                    <template v-else>{{ task.step }}</template>
                </span>

                <div class="task-info">
                    <span class="task-title">{{ task.title }}</span>
                    <span class="task-type">
                        {{ getTaskIcon(task.type) }} {{ task.max_score }} ball
                    </span>
                </div>

                <span v-if="tasksProgress[task.step]?.score" class="task-score">
                    {{ tasksProgress[task.step].score }}/{{ task.max_score }}
                </span>
            </button>
        </div>

        <!-- Current Task Details -->
        <div v-if="tasks[currentTask - 1]" class="current-task-details">
            <h4>{{ tasks[currentTask - 1].title }}</h4>
            <p>{{ tasks[currentTask - 1].description }}</p>

            <div v-if="tasks[currentTask - 1].hints?.length" class="hints">
                <details>
                    <summary>💡 Ko'rsatmalar</summary>
                    <ul>
                        <li v-for="(hint, i) in tasks[currentTask - 1].hints" :key="i">
                            {{ hint }}
                        </li>
                    </ul>
                </details>
            </div>
        </div>
    </div>
</template>

<style scoped>
.task-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.panel-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}

.tasks-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    overflow-y: auto;
}

.task-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    color: white;
}

.task-item:hover {
    border-color: #475569;
}

.task-item.active {
    border-color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.task-item.completed {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.task-item.pending {
    opacity: 0.6;
}

.task-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    flex-shrink: 0;
}

.task-item.completed .task-number {
    background: #10b981;
    color: white;
}

.task-info {
    flex: 1;
    min-width: 0;
}

.task-title {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.task-type {
    font-size: 0.75rem;
    color: #64748b;
}

.task-score {
    font-size: 0.8rem;
    font-weight: 600;
    color: #10b981;
}

.current-task-details {
    margin-top: auto;
    padding: 1rem;
    background: #0f172a;
    border-radius: 10px;
    border: 1px solid #334155;
}

.current-task-details h4 {
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.current-task-details p {
    font-size: 0.85rem;
    color: #94a3b8;
    line-height: 1.5;
}

.hints {
    margin-top: 0.75rem;
}

.hints summary {
    font-size: 0.8rem;
    color: #f59e0b;
    cursor: pointer;
}

.hints ul {
    margin-top: 0.5rem;
    padding-left: 1.25rem;
    font-size: 0.8rem;
    color: #94a3b8;
}

.hints li {
    margin-bottom: 0.25rem;
}
</style>
