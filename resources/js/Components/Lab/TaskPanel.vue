<script setup>
import { computed } from 'vue';
import { taskAnswerKind, nextOpenStep } from './labTasks';

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    currentTask: Number,
    tasksProgress: { type: Object, default: () => ({}) },
    feedback: { type: Object, default: null }, // { step, correct, message }
    checking: { type: Boolean, default: false },
    answerText: { type: String, default: '' },
});

defineEmits(['selectTask', 'check', 'update:answerText']);

const activeTask = computed(() => props.tasks.find((t) => t.step === props.currentTask) || null);
const activeKind = computed(() => taskAnswerKind(activeTask.value));
const activeProgress = computed(() => (activeTask.value ? props.tasksProgress[activeTask.value.step] : null));
const activeFeedback = computed(() =>
    props.feedback && activeTask.value && props.feedback.step === activeTask.value.step ? props.feedback : null
);

// Next unfinished task (null when this is the last open one)
const nextStep = computed(() => {
    if (!activeTask.value) return null;
    const next = nextOpenStep(props.tasks, props.tasksProgress, activeTask.value.step);
    return next !== activeTask.value.step ? next : null;
});

const answerHint = {
    measurement: "Vazifa shartidagi sozlamalarni o'rnatib, simulyatsiyada o'lchovni bajaring — natija avtomatik tekshiriladi.",
    calculation: "Kalkulyatorda vazifada ko'rsatilgan formula bo'yicha hisoblang — natija avtomatik tekshiriladi.",
    text: "Kuzatishlaringiz va xulosangizni o'z so'zlaringiz bilan yozing.",
};

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
        <div v-if="activeTask" class="current-task-details">
            <h4>{{ activeTask.title }}</h4>
            <p>{{ activeTask.description }}</p>

            <div v-if="activeTask.hints?.length" class="hints">
                <details>
                    <summary>💡 Ko'rsatmalar</summary>
                    <ul>
                        <li v-for="(hint, i) in activeTask.hints" :key="i">
                            {{ hint }}
                        </li>
                    </ul>
                </details>
            </div>

            <!-- Answer / check -->
            <div class="task-answer">
                <template v-if="activeProgress?.completed">
                    <div class="task-done">
                        ✓ Bajarildi — {{ activeProgress.score }}/{{ activeTask.max_score }} ball
                    </div>
                    <button v-if="nextStep" type="button" class="check-btn" @click="$emit('selectTask', nextStep)">
                        Keyingi vazifa →
                    </button>
                </template>

                <template v-else>
                    <p v-if="answerHint[activeKind]" class="answer-hint">{{ answerHint[activeKind] }}</p>

                    <textarea v-if="activeKind === 'text'" class="answer-text" rows="4"
                        :value="answerText" :disabled="checking" maxlength="5000"
                        placeholder="Javobingizni shu yerga yozing..."
                        @input="$emit('update:answerText', $event.target.value)"></textarea>

                    <button type="button" class="check-btn" :disabled="checking" @click="$emit('check')">
                        <template v-if="checking">Tekshirilmoqda...</template>
                        <template v-else-if="activeKind === 'ack'">✓ Tanishdim</template>
                        <template v-else>🔍 Tekshirish</template>
                    </button>
                </template>

                <div v-if="activeFeedback" class="task-feedback" :class="activeFeedback.correct ? 'ok' : 'wrong'"
                    role="status">
                    <span class="fb-icon">{{ activeFeedback.correct ? '✓' : '✗' }}</span>
                    <span>{{ activeFeedback.message }}</span>
                </div>
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

.task-answer {
    margin-top: 0.875rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.current-task-details .answer-hint {
    font-size: 0.78rem;
    color: #64748b;
}

.answer-text {
    width: 100%;
    padding: 0.5rem 0.625rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 8px;
    color: white;
    font-size: 0.85rem;
    line-height: 1.4;
    resize: vertical;
}

.answer-text:focus {
    outline: none;
    border-color: #3b82f6;
}

.check-btn {
    padding: 0.625rem;
    background: #3b82f6;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.check-btn:hover:not(:disabled) {
    background: #2563eb;
}

.check-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.task-done {
    padding: 0.5rem 0.625rem;
    border-radius: 8px;
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    font-size: 0.85rem;
    font-weight: 600;
}

.task-feedback {
    display: flex;
    gap: 0.5rem;
    padding: 0.5rem 0.625rem;
    border-radius: 8px;
    font-size: 0.8rem;
    line-height: 1.4;
}

.task-feedback.ok {
    background: rgba(16, 185, 129, 0.12);
    color: #34d399;
}

.task-feedback.wrong {
    background: rgba(239, 68, 68, 0.12);
    color: #f87171;
}

.fb-icon {
    font-weight: 700;
}
</style>
