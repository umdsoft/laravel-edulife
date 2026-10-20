<script setup>
/**
 * Shanba imtihoni — result of one attempt: score, change vs last week, per-topic points and, once
 * allowed (placement: at once, weekly: after the session closes), a per-question review with the
 * correct answer and the solution (text / video) for the questions answered wrong.
 */
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowLeftIcon, PresentationChartLineIcon, PhotoIcon, PlayCircleIcon, LockClosedIcon, ClockIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon, XCircleIcon, MinusCircleIcon, ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import MathText from '@/Components/MathText.vue';
import QuestionImage from '@/Components/Media/QuestionImage.vue'; // media (agent M)
import { OPTION_GRID, optionsHaveMedia } from '@/Components/Media/optionMedia';
import RichContent from '@/Components/Student/RichContent.vue';
import { Alert, Badge, Button, Card, PageHeader, ProgressBar, ProgressRing } from '@/Components/UI';

const props = defineProps({
    attempt: { type: Object, required: true },
    session: { type: Object, required: true },
    weekly: { type: Object, default: null },
    topics: { type: Array, default: () => [] },
    reviewAvailable: { type: Boolean, default: false },
    review: { type: Array, default: () => [] },
    links: { type: Object, default: () => ({}) },
});

const graded = computed(() => props.attempt.status === 'graded');
const percent = computed(() => Math.round(props.attempt.score_percent || 0));
const tone = computed(() => (percent.value >= 70 ? 'success' : percent.value >= 40 ? 'warning' : 'danger'));
const num = (v) => String(Math.round((Number(v) || 0) * 100) / 100).replace('.', ',');
const pad = (n) => String(n).padStart(2, '0');
const dateTime = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const minutes = (s) => (s === null || s === undefined ? '—' : `${Math.max(1, Math.round(s / 60))} daqiqa`);
const isPlacement = computed(() => props.session.type === 'placement');
const delta = computed(() => props.weekly?.delta_prev ?? null);

const statusOf = (q) => {
    if (q.voided) return { label: 'Bekor qilingan savol', variant: 'neutral', icon: MinusCircleIcon, cls: 'text-fg-subtle' };
    if (q.is_correct === true) return { label: "To'g'ri", variant: 'success', icon: CheckCircleIcon, cls: 'text-success' };
    if (q.is_correct === false && q.points > 0) return { label: 'Birlik xato (qisman)', variant: 'warning', icon: XCircleIcon, cls: 'text-warning' };
    if (q.is_correct === false) return { label: "Noto'g'ri", variant: 'danger', icon: XCircleIcon, cls: 'text-danger' };
    return { label: 'Javob berilmagan', variant: 'neutral', icon: MinusCircleIcon, cls: 'text-fg-subtle' };
};
const optionClass = (o) => {
    if (o.is_correct) return 'border-success-600 bg-success-50';
    if (o.selected) return 'border-danger-600 bg-danger-50';
    return 'border-border bg-surface';
};
const letter = (i) => String.fromCharCode(65 + i);
const youtubeEmbed = (url) => {
    const m = String(url || '').match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
    return m ? `https://www.youtube-nocookie.com/embed/${m[1]}` : null;
};
</script>

<template>
    <Head :title="`Natija — ${session.title}`" />

    <StudentLayout title="Natija">
        <PageHeader :title="session.title" :back="route('student.exam.index')" back-label="Shanba imtihoni">
            <template #meta>
                <Badge v-if="session.subject" :variant="session.subject.code === 'physics' ? 'physics' : 'math'">{{ session.subject.name }}</Badge>
                <Badge variant="neutral">{{ isPlacement ? 'Qabul testi' : 'Shanba imtihoni' }}</Badge>
                <Badge v-if="attempt.auto_submitted" variant="warning" :icon="ClockIcon">Vaqt tugaganda topshirildi</Badge>
            </template>
            <template #actions>
                <Button v-if="links.progress" variant="secondary" :icon-left="PresentationChartLineIcon" :href="links.progress">O'sishim</Button>
                <Button v-if="links.card && !isPlacement" variant="ghost" :icon-left="PhotoIcon" :href="links.card" external>Natija kartasi</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="!graded" variant="info" title="Natija hisoblanmoqda">Bir oz kuting va sahifani yangilang.</Alert>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Score -->
                <Card padding="lg" class="lg:col-span-2">
                    <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                        <ProgressRing :value="percent" :size="120" :stroke="10" :tone="tone" :label="`Natija ${percent} foiz`">
                            <div class="text-center">
                                <div class="text-2xl font-semibold tabular-nums text-fg">{{ percent }}%</div>
                            </div>
                        </ProgressRing>
                        <div class="w-full min-w-0 flex-1 space-y-3">
                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <p class="text-3xl font-semibold tabular-nums text-fg">{{ num(attempt.score) }}<span class="text-lg text-fg-subtle"> / {{ num(attempt.max_score) }} ball</span></p>
                                <p v-if="delta !== null" :class="['inline-flex items-center gap-1 text-sm font-semibold tabular-nums', delta >= 0 ? 'text-success' : 'text-danger']">
                                    <component :is="delta >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="h-4 w-4" aria-hidden="true" />
                                    {{ delta > 0 ? '+' : '' }}{{ num(delta) }} o'tgan haftaga nisbatan
                                </p>
                            </div>
                            <dl class="grid grid-cols-3 gap-3 text-center">
                                <div class="rounded-md bg-success-50 px-2 py-2.5">
                                    <dt class="text-xs text-fg-muted">To'g'ri</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-success">{{ attempt.correct_count }}</dd>
                                </div>
                                <div class="rounded-md bg-danger-50 px-2 py-2.5">
                                    <dt class="text-xs text-fg-muted">Xato</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-danger">{{ attempt.wrong_count }}</dd>
                                </div>
                                <div class="rounded-md bg-surface-2 px-2 py-2.5">
                                    <dt class="text-xs text-fg-muted">Javobsiz</dt>
                                    <dd class="text-lg font-semibold tabular-nums text-fg">{{ attempt.skipped_count }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </Card>

                <Card title="Ma'lumot">
                    <dl class="divide-y divide-border text-sm">
                        <div v-if="weekly?.rank" class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">O'rin</dt>
                            <dd class="font-medium tabular-nums">{{ weekly.rank }} / {{ weekly.participants_count }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Topshirildi</dt>
                            <dd class="font-medium tabular-nums">{{ dateTime(attempt.submitted_at) }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Sarflangan vaqt</dt>
                            <dd class="font-medium tabular-nums">{{ minutes(attempt.time_spent_seconds) }}</dd>
                        </div>
                    </dl>
                    <p v-if="!isPlacement && !weekly?.rank" class="mt-3 text-xs text-fg-subtle">O'rin imtihon yopilgandan keyin hisoblanadi.</p>
                </Card>
            </div>

            <!-- Topics -->
            <Card v-if="topics.length" title="Mavzular bo'yicha" description="Eng zaif mavzular yuqorida">
                <ul class="space-y-3">
                    <li v-for="t in topics" :key="t.topic_id">
                        <div class="mb-1 flex items-baseline justify-between gap-3 text-13">
                            <span class="min-w-0 truncate text-fg">{{ t.name }}</span>
                            <span class="shrink-0 tabular-nums text-fg-muted">{{ num(t.points) }} / {{ num(t.max) }}</span>
                        </div>
                        <ProgressBar :value="t.percent" size="sm" :tone="t.percent >= 70 ? 'success' : t.percent >= 40 ? 'warning' : 'danger'" />
                    </li>
                </ul>
            </Card>

            <!-- Review -->
            <section v-if="reviewAvailable" aria-labelledby="review-title" class="space-y-3">
                <h2 id="review-title" class="text-lg font-semibold text-fg">Savollar tahlili</h2>
                <Card v-for="q in review" :key="q.id" padding="md">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <p class="text-13 font-medium text-fg-subtle">Savol <span class="tabular-nums text-fg">{{ q.position }}</span></p>
                        <Badge :variant="statusOf(q).variant" :icon="statusOf(q).icon">
                            {{ statusOf(q).label }}<template v-if="!q.voided"> · {{ num(q.points) }}/{{ num(q.max_points) }}</template>
                        </Badge>
                    </div>
                    <MathText :text="q.text" tag="div" class="block max-w-[70ch] text-[15px] leading-relaxed text-fg" />
                    <img v-if="q.image" :src="q.image" alt="Savol rasmi" class="mt-3 max-h-64 rounded-md border border-border" loading="lazy">

                    <ul v-if="q.options.length" :class="['mt-4', optionsHaveMedia(q.options) ? OPTION_GRID : 'space-y-2']">
                        <li v-for="(o, i) in q.options" :key="o.id" :class="['flex flex-wrap items-start gap-3 rounded-md border p-3 text-[15px]', optionClass(o)]">
                            <span class="w-5 shrink-0 font-semibold text-fg-subtle">{{ letter(i) }}</span>
                            <MathText :text="o.content" class="min-w-0 flex-1 text-fg" />
                            <QuestionImage v-if="o.image" :src="o.image" :alt="`${letter(i)} variant rasmi`" class="basis-full" />
                            <span v-if="o.is_correct" class="shrink-0 text-xs font-medium text-success">To'g'ri javob</span>
                            <span v-else-if="o.selected" class="shrink-0 text-xs font-medium text-danger">Sizning javobingiz</span>
                        </li>
                    </ul>
                    <dl v-else class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-md bg-surface-2 px-3 py-2.5">
                            <dt class="text-xs text-fg-muted">Sizning javobingiz</dt>
                            <dd class="font-medium tabular-nums text-fg">{{ q.given || '—' }}</dd>
                        </div>
                        <div class="rounded-md bg-success-50 px-3 py-2.5">
                            <dt class="text-xs text-fg-muted">To'g'ri javob</dt>
                            <dd class="font-medium tabular-nums text-success">
                                <span v-for="(e, i) in q.expected" :key="i">{{ i ? ' yoki ' : '' }}{{ e.value }}<template v-if="e.unit"> {{ e.unit }}</template></span>
                            </dd>
                        </div>
                    </dl>

                    <div v-if="q.solution_text || q.solution_video_url" class="mt-4 space-y-3 border-t border-border pt-4">
                        <h3 class="text-13 font-semibold text-fg">Yechim</h3>
                        <RichContent v-if="q.solution_text" :html="q.solution_text" size="sm" />
                        <div v-if="q.solution_video_url">
                            <div v-if="youtubeEmbed(q.solution_video_url)" class="aspect-video overflow-hidden rounded-md border border-border">
                                <iframe :src="youtubeEmbed(q.solution_video_url)" title="Yechim videosi" class="h-full w-full" loading="lazy"
                                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen />
                            </div>
                            <Button v-else variant="soft" size="sm" :icon-left="PlayCircleIcon" :href="q.solution_video_url" external>Yechim videosi</Button>
                        </div>
                    </div>
                </Card>
            </section>
            <Card v-else-if="graded" padding="lg">
                <div class="flex items-start gap-3">
                    <LockClosedIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                    <div>
                        <p class="font-medium text-fg">Savollar tahlili imtihon yopilgandan keyin ochiladi</p>
                        <p class="mt-1 text-13 text-fg-muted">Boshqa o'quvchilar hali yechayotgan bo'lishi mumkin. To'g'ri javoblar va yechim videolari {{ session.ends_at ? dateTime(session.ends_at) : 'imtihon tugagach' }} dan keyin ko'rinadi.</p>
                    </div>
                </div>
            </Card>

            <div>
                <Button variant="ghost" :icon-left="ArrowLeftIcon" :href="route('student.exam.index')">Shanba imtihoniga qaytish</Button>
            </div>
        </div>
    </StudentLayout>
</template>
