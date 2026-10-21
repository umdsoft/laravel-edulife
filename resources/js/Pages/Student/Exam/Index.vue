<script setup>
/**
 * Shanba imtihoni — the student's exam home: running attempt, next Saturday session with a
 * countdown, the free placement test per subject, and the history of finished attempts.
 * Paid exams: each session shows how the student gets in (SessionAccess: free / subscription /
 * paid / Payme-Click buttons); after a payment the provider sends the student back here (?paid=…).
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDaysIcon, ClockIcon, AcademicCapIcon, ArrowRightIcon, PresentationChartLineIcon,
    ChartBarSquareIcon, ClipboardDocumentCheckIcon, PlayIcon,
} from '@heroicons/vue/24/outline';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';
import SessionAccess from '@/Components/Exam/SessionAccess.vue';
import { onAppVisibility } from '@/Composables/useTelegram';

const props = defineProps({
    placement: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    running: { type: Object, default: null },
    serverNow: { type: String, default: null },
    needsOnboarding: { type: Boolean, default: false },
    links: { type: Object, default: () => ({}) },
    payments: { type: Object, default: () => ({ providers: [], exam_plans: [] }) },
    paymentNotice: { type: Object, default: null },
});

// server clock offset, so the countdown does not depend on the device clock
const offset = props.serverNow ? new Date(props.serverNow).getTime() - Date.now() : 0;
const now = ref(Date.now() + offset);
let timer = null;
onMounted(() => { timer = setInterval(() => { now.value = Date.now() + offset; }, 1000); });
onBeforeUnmount(() => clearInterval(timer));

const next = computed(() => props.upcoming[0] || null);
const others = computed(() => props.upcoming.slice(1));

const pad = (n) => String(n).padStart(2, '0');
const countdown = (iso) => {
    const diff = Math.max(0, Math.floor((new Date(iso).getTime() - now.value) / 1000));
    const d = Math.floor(diff / 86400);
    const h = Math.floor((diff % 86400) / 3600);
    const m = Math.floor((diff % 3600) / 60);
    const s = diff % 60;
    return { d, h: pad(h), m: pad(m), s: pad(s), done: diff === 0 };
};
const isLive = (s) => s.status === 'open' || (s.starts_at && new Date(s.starts_at).getTime() <= now.value);

const DAYS = ['yakshanba', 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba'];
const dateLabel = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}, ${DAYS[d.getDay()]}`;
};
const timeLabel = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const shortDate = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
const pct = (v) => `${Math.round(Number(v) || 0)}%`;
const subjectVariant = (code) => (code === 'physics' ? 'physics' : 'math');
const typeLabel = (t) => ({ placement: 'Qabul testi', weekly: 'Shanba imtihoni', open_regional: 'Ochiq imtihon' }[t] || 'Imtihon');

// ---- paid exams ----
const needsPayment = (s) => s.access?.state === 'pay' && !s.attempt;
const planNames = computed(() => [...new Set((props.payments?.exam_plans || []).map((n) => n.replace(/\s+yillik$/i, '')))]);
const anyPaid = computed(() => props.upcoming.some((s) => (s.access?.price || 0) > 0));

const RELOAD = ['upcoming', 'paymentNotice', 'payments', 'running'];
const refreshAccess = () => router.reload({ only: RELOAD, preserveScroll: true });

// back from Payme / Click: the callback may come a few seconds later — refresh until it does (2 min)
let noticeTimer = null;
let noticeTries = 0;
const waitingForPayment = computed(() => ['pending', 'processing'].includes(props.paymentNotice?.status));
const NOTICE = {
    paid: { variant: 'success', title: "To'lov qabul qilindi", text: (n) => `«${n.title}» imtihoniga kirish ochiq.` },
    processing: { variant: 'info', title: "To'lov tekshirilmoqda…", text: () => "Payme yoki Click tasdig'i kelishi bilan sahifa o'zi yangilanadi." },
    pending: { variant: 'info', title: "To'lov kutilmoqda", text: () => "To'lovni yakunlagan bo'lsangiz, tasdiq kelishi bilan sahifa yangilanadi." },
    cancelled: { variant: 'warning', title: "To'lov yakunlanmadi", text: () => "Pul yechilmadi. Qaytadan to'lashingiz mumkin." },
    refunded: { variant: 'neutral', title: "To'lov qaytarilgan", text: () => "Bu to'lov qaytarilgan." },
};
const notice = computed(() => (props.paymentNotice ? NOTICE[props.paymentNotice.status] || null : null));
onMounted(() => {
    noticeTimer = setInterval(() => {
        if (!waitingForPayment.value || noticeTries >= 40) return;
        noticeTries += 1;
        refreshAccess();
    }, 3000);
});
onBeforeUnmount(() => clearInterval(noticeTimer));
// Telegram: the payment page was opened outside the Mini App — refresh when the student comes back
onAppVisibility((visible) => { if (visible) refreshAccess(); });

const starting = ref(null);
const startSession = (session) => {
    starting.value = session.id;
    router.post(route('student.exam.sessions.start', session.id), {}, { onFinish: () => { starting.value = null; } });
};
const startPlacement = (card) => {
    starting.value = card.subject.code;
    router.post(route('student.exam.placement.start', card.subject.code), {}, { onFinish: () => { starting.value = null; } });
};
</script>

<template>
    <Head title="Shanba imtihoni" />

    <StudentLayout title="Shanba imtihoni">
        <PageHeader title="Shanba imtihoni"
            description="Har shanba — shaxsiy variant, vaqt cheklangan, natija va o'sish darhol ko'rinadi.">
            <template #actions>
                <Button v-if="links.progress" variant="secondary" :icon-left="PresentationChartLineIcon" :href="links.progress">O'sishim</Button>
                <Button v-if="links.leaderboard" variant="ghost" :icon-left="ChartBarSquareIcon" :href="links.leaderboard" class="hidden sm:inline-flex">Reyting</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="running" variant="warning" title="Boshlangan imtihon bor">
                «{{ running.title }}» davom etmoqda. Vaqt tugashidan oldin yakunlang.
                <template #actions>
                    <Button size="sm" :href="route('student.exam.take', running.id)" :icon-left="PlayIcon">Davom ettirish</Button>
                </template>
            </Alert>

            <Alert v-if="notice" :variant="notice.variant" :title="notice.title" data-testid="payment-notice">
                {{ notice.text(paymentNotice) }}
                <template v-if="paymentNotice.retry_url || paymentNotice.status === 'paid'" #actions>
                    <Button v-if="paymentNotice.retry_url" size="sm" variant="secondary" :href="paymentNotice.retry_url" external>To'lov sahifasini ochish</Button>
                </template>
            </Alert>

            <Alert v-if="needsOnboarding && !links.onboarding" variant="info" title="Profil to'liq emas">
                Imtihonda qatnashish uchun fanlaringiz va ota-ona roziligi kerak. Ustozingizga murojaat qiling.
            </Alert>

            <!-- Next Saturday session -->
            <Card v-if="next" padding="lg">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge :variant="subjectVariant(next.subject.code)">{{ next.subject.name }}</Badge>
                            <Badge v-if="isLive(next)" variant="success" dot>Hozir ochiq</Badge>
                            <Badge v-else variant="neutral">Keyingi imtihon</Badge>
                            <SessionAccess v-if="next.access" :session="next" :payments="payments" badge-only />
                        </div>
                        <h2 class="text-lg font-semibold text-fg">{{ next.title }}</h2>
                        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-13 text-fg-muted">
                            <span class="inline-flex items-center gap-1.5"><CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />{{ dateLabel(next.starts_at) }}</span>
                            <span class="inline-flex items-center gap-1.5 tabular-nums"><ClockIcon class="h-4 w-4" aria-hidden="true" />{{ timeLabel(next.starts_at) }}–{{ timeLabel(next.ends_at) }} · {{ next.duration_min }} daqiqa</span>
                        </p>
                    </div>

                    <div class="shrink-0">
                        <template v-if="next.attempt && next.attempt.status !== 'in_progress'">
                            <Button variant="secondary" :href="route('student.exam.result', next.attempt.id)" :icon-right="ArrowRightIcon">Natijani ko'rish</Button>
                        </template>
                        <template v-else-if="next.attempt">
                            <Button :href="route('student.exam.take', next.attempt.id)" :icon-left="PlayIcon">Davom ettirish</Button>
                        </template>
                        <template v-else-if="isLive(next) && !needsPayment(next)">
                            <Button size="lg" :loading="starting === next.id" :icon-left="PlayIcon" @click="startSession(next)">Imtihonni boshlash</Button>
                        </template>
                        <div v-else-if="!isLive(next)" class="flex items-center gap-2" role="timer" :aria-label="`Boshlanishiga ${countdown(next.starts_at).d} kun ${countdown(next.starts_at).h} soat ${countdown(next.starts_at).m} daqiqa`">
                            <div v-for="part in [
                                { v: countdown(next.starts_at).d, l: 'kun' },
                                { v: countdown(next.starts_at).h, l: 'soat' },
                                { v: countdown(next.starts_at).m, l: 'daq' },
                                { v: countdown(next.starts_at).s, l: 'son' },
                            ]" :key="part.l" class="w-14 rounded-md border border-border bg-surface-2 py-2 text-center" aria-hidden="true">
                                <div class="text-xl font-semibold tabular-nums text-fg">{{ part.v }}</div>
                                <div class="text-xs text-fg-subtle">{{ part.l }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <SessionAccess v-if="needsPayment(next)" :session="next" :payments="payments" class="mt-5" />

                <ul v-if="others.length" class="mt-5 divide-y divide-border border-t border-border pt-1 text-13">
                    <li v-for="s in others" :key="s.id" class="py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="min-w-0 truncate text-fg-muted">
                                <Badge :variant="subjectVariant(s.subject.code)" size="sm" class="mr-2">{{ s.subject.name }}</Badge>{{ s.title }}
                            </span>
                            <Button v-if="isLive(s) && !s.attempt && !needsPayment(s)" size="sm" variant="soft" :loading="starting === s.id" @click="startSession(s)">Boshlash</Button>
                            <span v-else class="shrink-0 tabular-nums text-fg-subtle">{{ shortDate(s.starts_at) }} {{ timeLabel(s.starts_at) }}</span>
                        </div>
                        <div v-if="s.access && (anyPaid || s.access.state !== 'free')" class="mt-2">
                            <SessionAccess :session="s" :payments="payments" />
                        </div>
                    </li>
                </ul>
            </Card>
            <Card v-else padding="lg">
                <EmptyState :icon="CalendarDaysIcon" title="Hozircha rejalashtirilgan imtihon yo'q"
                    description="Ustoz keyingi shanba imtihonini e'lon qilganda shu yerda vaqti va qolgan muddat ko'rinadi." size="sm" />
            </Card>

            <!-- Paid exams: which plans include the Saturday exams -->
            <Alert v-if="payments.subscription" variant="success" title="Obuna bilan barcha shanba imtihonlari ochiq">
                «{{ payments.subscription.plan }}» tarifi<template v-if="payments.subscription.ends_at"> {{ payments.subscription.ends_at }} gacha</template> faol — pullik imtihonlarni alohida to'lamaysiz.
            </Alert>
            <Card v-else-if="planNames.length && (anyPaid || !upcoming.length)" padding="md" data-testid="exam-plans-hint">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium text-fg">Har imtihonga alohida to'lamang</p>
                        <p class="mt-0.5 text-13 text-fg-muted">
                            {{ planNames.join(', ') }} tariflarida barcha shanba imtihonlari kiradi. Qabul testi har doim bepul.
                        </p>
                    </div>
                    <Button v-if="payments.plans_url" variant="secondary" size="sm" :href="payments.plans_url" :icon-right="ArrowRightIcon" class="shrink-0">
                        Tariflarni ko'rish
                    </Button>
                </div>
            </Card>

            <!-- Placement per subject -->
            <section aria-labelledby="placement-title" class="space-y-3">
                <div>
                    <h2 id="placement-title" class="text-lg font-semibold text-fg">Qabul testi</h2>
                    <p class="text-13 text-fg-muted">Bepul, har fan bo'yicha bir marta. Natija mavzular xaritangizning boshlang'ich nuqtasi bo'ladi.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Card v-for="card in placement" :key="card.subject.code" padding="md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Badge :variant="subjectVariant(card.subject.code)">{{ card.subject.name }}</Badge>
                                <p v-if="card.questions" class="mt-2 text-13 text-fg-muted tabular-nums">{{ card.questions }} savol · {{ card.duration_min }} daqiqa</p>
                            </div>
                            <AcademicCapIcon class="h-6 w-6 shrink-0 text-fg-subtle" aria-hidden="true" />
                        </div>

                        <div class="mt-4">
                            <template v-if="card.state === 'available' || card.state === 'retake'">
                                <p v-if="card.state === 'retake'" class="mb-3 text-13 text-fg-muted">Ustoz qayta topshirishga ruxsat berdi.</p>
                                <Button block :loading="starting === card.subject.code" @click="startPlacement(card)">
                                    {{ card.state === 'retake' ? 'Qayta topshirish' : 'Qabul testini boshlash' }}
                                </Button>
                            </template>
                            <Button v-else-if="card.state === 'in_progress' && card.attempt" block :href="route('student.exam.take', card.attempt.id)" :icon-left="PlayIcon">
                                Davom ettirish
                            </Button>
                            <div v-else-if="card.state === 'done' && card.attempt" class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-2xl font-semibold tabular-nums text-fg">{{ pct(card.attempt.score_percent) }}</p>
                                    <p class="text-xs text-fg-subtle">Topshirilgan: {{ shortDate(card.attempt.submitted_at) }}</p>
                                </div>
                                <Button variant="secondary" size="sm" :href="route('student.exam.result', card.attempt.id)">Natija</Button>
                            </div>
                            <p v-else class="text-13 text-fg-subtle">Bu fan bo'yicha qabul testi hali tayyor emas.</p>
                        </div>
                    </Card>
                </div>
                <EmptyState v-if="!placement.length" :icon="AcademicCapIcon" title="Fanlar tanlanmagan" size="sm" bordered
                    description="Profilingizda matematika yoki fizikani tanlang." />
            </section>

            <!-- History -->
            <Card padding="none" title="Natijalarim" description="Topshirilgan imtihonlar">
                <ul v-if="history.length" class="divide-y divide-border">
                    <li v-for="h in history" :key="h.id">
                        <Link :href="route('student.exam.result', h.id)"
                            class="focus-ring flex items-center gap-3 px-5 py-3.5 transition-colors duration-150 hover:bg-surface-2/60">
                            <ClipboardDocumentCheckIcon class="hidden h-5 w-5 shrink-0 text-fg-subtle sm:block" aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-fg">{{ h.title }}</p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-fg-subtle">
                                    <Badge :variant="subjectVariant(h.subject.code)" size="sm">{{ h.subject.name }}</Badge>
                                    <span>{{ typeLabel(h.type) }}</span>
                                    <span class="tabular-nums">{{ shortDate(h.submitted_at) }}</span>
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-semibold tabular-nums text-fg">{{ pct(h.score_percent) }}</p>
                                <p v-if="h.delta_prev !== null" :class="['inline-flex items-center gap-0.5 text-xs font-medium tabular-nums', h.delta_prev >= 0 ? 'text-success' : 'text-danger']">
                                    <component :is="h.delta_prev >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ h.delta_prev > 0 ? '+' : '' }}{{ Math.round(h.delta_prev) }}
                                </p>
                            </div>
                        </Link>
                    </li>
                </ul>
                <EmptyState v-else :icon="ClipboardDocumentCheckIcon" title="Hali natija yo'q" size="sm"
                    description="Qabul testi yoki shanba imtihonini topshirganingizdan keyin natijalar shu yerda turadi." />
            </Card>
        </div>
    </StudentLayout>
</template>
