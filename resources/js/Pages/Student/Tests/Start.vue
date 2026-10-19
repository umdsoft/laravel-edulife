<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import {
    ClockIcon, QuestionMarkCircleIcon, FlagIcon, ArrowPathIcon, ShieldExclamationIcon, LockClosedIcon,
} from '@heroicons/vue/24/outline';
import { PlayIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, Button, Checkbox, Alert, Badge } from '@/Components/UI';
import { formatDate } from '@/Components/Student/format.js';

const props = defineProps({
    test: Object,
    course: Object,
    previousAttempts: Array,
    canStart: Boolean,
    prerequisitesMet: Boolean,
    prerequisiteMessage: { type: String, default: null },
    bestScore: Number,
    isPassed: Boolean,
    remainingAttempts: Number
});

const form = useForm({
    confirm: false
});

const startTest = () => {
    form.post(route('student.tests.begin', props.test.id));
};

const facts = [
    { key: 'questions_count', label: 'Savollar', icon: QuestionMarkCircleIcon, suffix: 'ta' },
    { key: 'time_limit', label: 'Vaqt', icon: ClockIcon, suffix: 'daqiqa' },
    { key: 'pass_rate', label: "O'tish balli", icon: FlagIcon, suffix: '%' },
    { key: 'max_attempts', label: 'Urinishlar', icon: ArrowPathIcon, suffix: 'ta' },
];

const rules = [
    "Test boshlangach, vaqt to'xtamaydi.",
    "Boshqa oyna yoki ilovaga o'tish qayd etiladi — ogohlantirishlar limitga yetsa, test yakunlanadi.",
    "Vaqt tugashi bilan test avtomatik topshiriladi.",
    "Javoblar har bir savoldan o'tganda saqlanadi.",
    "Natija darhol ko'rsatiladi; javoblar tahlili test sozlamalariga bog'liq.",
];
</script>

<template>
    <StudentLayout :title="test.title">

        <Head :title="test.title" />

        <div class="mx-auto max-w-4xl">
            <PageHeader :title="test.title" :description="course.title" :back="route('student.tests.index', course.id)"
                back-label="Testlar ro'yxati" />

            <div class="grid gap-6 md:grid-cols-3 md:items-start">
                <div class="space-y-6 md:col-span-2">
                    <Card title="Test haqida">
                        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div v-for="f in facts" :key="f.key" class="rounded-md bg-surface-2/70 p-3">
                                <dt class="flex items-center gap-1.5 text-xs text-fg-subtle">
                                    <component :is="f.icon" class="h-4 w-4" aria-hidden="true" />{{ f.label }}
                                </dt>
                                <dd v-if="f.key === 'max_attempts' && !Number(test.max_attempts)" class="mt-1 text-sm font-semibold text-fg">
                                    Cheklanmagan
                                </dd>
                                <dd v-else class="mt-1 text-lg font-semibold text-fg tabular-nums">
                                    {{ test[f.key] ?? '—' }}<span class="ml-0.5 text-13 font-normal text-fg-subtle">{{ f.suffix }}</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-5 border-t border-border pt-4">
                            <h3 class="mb-2 text-13 font-medium text-fg">Savol turlari</h3>
                            <div class="flex flex-wrap gap-2">
                                <Badge>Bitta to'g'ri javob</Badge>
                                <Badge>Bir nechta to'g'ri javob</Badge>
                                <Badge>Bo'sh joyni to'ldirish</Badge>
                                <Badge>Moslashtirish</Badge>
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <ShieldExclamationIcon class="h-5 w-5 text-warning" aria-hidden="true" />
                                <h3 class="text-base font-semibold text-fg">Muhim qoidalar</h3>
                            </div>
                        </template>
                        <ol class="space-y-2.5 text-sm text-fg-muted">
                            <li v-for="(rule, i) in rules" :key="i" class="flex gap-3">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-surface-2 text-[11px] font-semibold text-fg-muted tabular-nums">{{ i + 1 }}</span>
                                <span>{{ rule }}</span>
                            </li>
                        </ol>
                    </Card>
                </div>

                <aside class="space-y-6 md:sticky md:top-20">
                    <Card title="Sizning holatingiz">
                        <dl class="divide-y divide-border text-sm">
                            <div class="flex justify-between py-2.5 first:pt-0">
                                <dt class="text-fg-muted">Eng yaxshi natija</dt>
                                <dd class="font-semibold tabular-nums" :class="isPassed ? 'text-success' : 'text-fg'">{{ Math.round(bestScore || 0) }}%</dd>
                            </div>
                            <div class="flex justify-between py-2.5 last:pb-0">
                                <dt class="text-fg-muted">Qolgan urinishlar</dt>
                                <dd class="font-semibold text-fg tabular-nums">{{ remainingAttempts ?? 'Cheklanmagan' }}</dd>
                            </div>
                        </dl>

                        <template #footer>
                            <Alert v-if="!prerequisitesMet" variant="danger" :icon="LockClosedIcon">
                                {{ prerequisiteMessage || 'Testni boshlash uchun avvalgi darslar yoki modullarni tugating.' }}
                            </Alert>
                            <Alert v-else-if="!canStart" variant="warning" :icon="LockClosedIcon">
                                Urinishlar soni tugadi.
                            </Alert>
                            <form v-else class="space-y-4 py-1" @submit.prevent="startTest">
                                <Checkbox v-model="form.confirm"
                                    label="Qoidalarni o'qidim va testni halol topshiraman." />
                                <Button type="submit" block size="lg" :icon-left="PlayIcon" :loading="form.processing"
                                    :disabled="!form.confirm">
                                    Testni boshlash
                                </Button>
                            </form>
                        </template>
                    </Card>

                    <Card v-if="previousAttempts.length > 0" title="Oldingi urinishlar" padding="sm">
                        <ul class="divide-y divide-border text-sm">
                            <li v-for="attempt in previousAttempts" :key="attempt.id" class="flex items-center justify-between py-2">
                                <span class="text-fg-muted tabular-nums">{{ formatDate(attempt.created_at) }}</span>
                                <span class="font-semibold tabular-nums" :class="attempt.is_passed ? 'text-success' : 'text-danger'">
                                    {{ Math.round(attempt.score) }}%
                                </span>
                            </li>
                        </ul>
                    </Card>
                </aside>
            </div>
        </div>
    </StudentLayout>
</template>
