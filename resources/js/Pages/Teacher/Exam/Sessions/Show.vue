<script setup>
/**
 * One exam session: bank sufficiency before it opens, variant pre-generation, live participation
 * while it runs, results with CSV export afterwards.
 */
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    PencilSquareIcon, CalendarDaysIcon, ArrowDownTrayIcon, Cog6ToothIcon, UsersIcon, DocumentDuplicateIcon,
    PlayIcon, CheckCircleIcon, ChartBarIcon, XMarkIcon, LockClosedIcon, ArrowPathIcon, BanknotesIcon,
} from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Alert, Badge, Button, Card, Modal, PageHeader, StatCard, Table } from '@/Components/UI';
import { sessionStatus, dateTimeLabel, timeLabel, num } from '@/Components/Exam/Engine/sessionFormat';

const props = defineProps({
    session: { type: Object, required: true },
    readiness: { type: Object, required: true },
    stats: { type: Object, required: true },
    payments: { type: Object, default: null },
    results: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    canRetake: { type: Boolean, default: false },
});

const live = reactive({ ...props.stats, status: props.session.status });
const isOpen = computed(() => live.status === 'open');
const isPlacement = computed(() => props.session.type === 'placement');
const bank = computed(() => props.readiness.bank);

let poll = null;
const refreshLive = async () => {
    try {
        const { data } = await axios.get(route('teacher.exam.sessions.live', props.session.id));
        const changed = data.finished !== live.finished || data.status !== live.status;
        Object.assign(live, data);
        if (changed) router.reload({ only: ['results', 'session'], preserveScroll: true });
    } catch { /* next tick */ }
};
onMounted(() => { if (isOpen.value) poll = setInterval(refreshLive, 15000); });
onBeforeUnmount(() => clearInterval(poll));

const BANK = {
    ok: { label: 'Bank yetarli', variant: 'success', text: "Barcha qoidalar tasdiqlangan savollar bilan to'liq to'ladi." },
    fallback: { label: "Qo'shni qiyinlik bilan", variant: 'warning', text: "Ba'zi qoidalar ±1 qiyinlikdagi savollar bilan to'ldiriladi." },
    short: { label: 'Bank yetarli emas', variant: 'danger', text: 'Variantlarda savollar kam bo\'ladi. Bankni to\'ldiring yoki rejani o\'zgartiring.' },
};
const bankState = computed(() => BANK[bank.value.status] || BANK.short);
const ruleStatus = { ok: 'success', fallback: 'warning', short: 'danger' };
const problemRules = computed(() => bank.value.rules.filter((r) => r.status !== 'ok'));
const showAllRules = ref(false);
const visibleRules = computed(() => (showAllRules.value ? bank.value.rules : problemRules.value));

const ruleColumns = [
    { key: 'topic', label: 'Mavzu' },
    { key: 'difficulty_level', label: 'Qiyinlik', align: 'center', hideBelow: 'sm' },
    { key: 'required', label: 'Kerak', numeric: true },
    { key: 'candidates', label: 'Bankda', numeric: true },
    { key: 'status', label: 'Holat' },
];

const resultColumns = computed(() => [
    ...(isPlacement.value ? [] : [{ key: 'rank', label: "O'rin", numeric: true, width: '64px' }]),
    { key: 'student', label: "O'quvchi" },
    { key: 'score_percent', label: 'Natija', numeric: true },
    { key: 'counts', label: "To'g'ri / xato / bo'sh", align: 'center', hideBelow: 'md' },
    { key: 'violations_count', label: 'Oyna', numeric: true, hideBelow: 'lg' },
    { key: 'status_label', label: 'Holat', hideBelow: 'sm' },
    ...(props.canRetake ? [{ key: 'actions', label: '', align: 'right' }] : []),
]);

const busy = ref(null);
const post = (name, data = {}, key = name) => {
    busy.value = key;
    router.post(route(name, props.session.id), data, { preserveScroll: true, onFinish: () => { busy.value = null; } });
};
const schedule = () => {
    if (bank.value.status === 'short') { confirm.value = 'force'; return; }
    post('teacher.exam.sessions.schedule');
};
const confirm = ref(null); // force | cancel | close
const confirmAction = () => {
    const action = confirm.value;
    confirm.value = null;
    if (action === 'force') post('teacher.exam.sessions.schedule', { force: true });
    if (action === 'cancel') post('teacher.exam.sessions.cancel');
    if (action === 'close') post('teacher.exam.sessions.close');
};
const retake = (row) => {
    busy.value = row.id;
    router.post(route('teacher.exam.sessions.retake', [props.session.id, row.id]), {}, { preserveScroll: true, onFinish: () => { busy.value = null; } });
};
// paid exams
const money = (v) => String(Math.round(Number(v) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const cancelText = computed(() => (props.payments?.purchased
    ? `Boshlangan urinishlar bekor qilinadi. ${props.payments.purchased} ta to'lov qaytarish ro'yxatiga tushadi (administrator qaytaradi). Bu amalni qaytarib bo'lmaydi.`
    : "Boshlangan urinishlar bekor qilinadi. Bu amalni qaytarib bo'lmaydi."));

const CONFIRM = {
    force: { title: 'Bank yetarli emas', text: "Baribir rejalashtirasizmi? Ba'zi o'quvchilar variantida savollar kam bo'ladi.", button: 'Rejalashtirish', variant: 'primary' },
    cancel: { title: 'Imtihonni bekor qilish', get text() { return cancelText.value; }, button: 'Bekor qilish', variant: 'danger' },
    close: { title: 'Imtihonni hozir yopish', text: 'Yechayotganlarning javoblari shu holatda topshiriladi va natijalar e\'lon qilinadi.', button: 'Yopish', variant: 'danger' },
};
</script>

<template>
    <Head :title="session.title" />

    <TeacherLayout>
        <PageHeader :title="session.title"
            :breadcrumbs="[{ label: 'Imtihonlar', href: route('teacher.exam.sessions.index') }, { label: session.title }]">
            <template #meta>
                <Badge :variant="sessionStatus(live.status).variant" dot>{{ sessionStatus(live.status).label }}</Badge>
                <Badge v-if="session.subject" :variant="session.subject.code === 'physics' ? 'physics' : 'math'">{{ session.subject.name }}</Badge>
                <Badge v-if="session.starts_at" variant="neutral" :icon="CalendarDaysIcon">
                    <span class="tabular-nums">{{ dateTimeLabel(session.starts_at) }}–{{ timeLabel(session.ends_at) }}</span>
                </Badge>
                <Badge v-if="session.restricted_to" variant="info">Faqat: {{ session.restricted_to }}</Badge>
            </template>
            <template #actions>
                <Button v-if="canEdit && !isPlacement" variant="secondary" :icon-left="PencilSquareIcon" :href="route('teacher.exam.sessions.edit', session.id)">Tahrirlash</Button>
                <Button v-if="live.status === 'draft'" :icon-left="CheckCircleIcon" :loading="busy === 'teacher.exam.sessions.schedule'" @click="schedule">Rejalashtirish</Button>
                <Button v-if="isOpen && !isPlacement" variant="secondary" :icon-left="LockClosedIcon" @click="confirm = 'close'">Hozir yopish</Button>
                <Button v-if="results.length" variant="ghost" :icon-left="ArrowDownTrayIcon" :href="route('teacher.exam.sessions.export', session.id)" external>CSV</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Participation -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Qatnashishi kerak" :value="live.audience" :icon="UsersIcon" tone="brand" hint="Profilida shu fan bor o'quvchilar" />
                <StatCard label="Tayyor variantlar" :value="live.variants" :icon="DocumentDuplicateIcon" tone="info" />
                <StatCard label="Hozir yechmoqda" :value="live.in_progress" :icon="PlayIcon" tone="warning" :hint="isOpen ? 'Har 15 soniyada yangilanadi' : null" />
                <StatCard label="Topshirdi" :value="live.finished" :icon="ChartBarIcon" tone="success"
                    :hint="live.average_percent !== null && live.average_percent !== undefined ? `O'rtacha ${num(live.average_percent)}%` : null" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Bank sufficiency -->
                <Card class="lg:col-span-2" title="Bank yetarliligi" :description="session.blueprint ? `Reja: ${session.blueprint.name} · ${session.blueprint.total_questions} savol` : null">
                    <template #actions>
                        <Button size="sm" variant="ghost" :icon-left="ArrowPathIcon" @click="router.reload({ only: ['readiness'], preserveScroll: true })">Yangilash</Button>
                    </template>
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Badge :variant="bankState.variant">{{ bankState.label }}</Badge>
                            <p class="text-13 text-fg-muted">{{ bankState.text }}</p>
                        </div>
                        <Alert v-if="!bank.consistent" variant="danger">Rejadagi savollar soni qoidalar yig'indisiga teng emas — rejani qayta saqlang.</Alert>

                        <Table v-if="visibleRules.length" :columns="ruleColumns" :rows="visibleRules" row-key="key" dense caption="Qoidalar bo'yicha bank">
                            <template #cell-topic="{ row }">
                                <span class="text-fg">{{ row.topic }}</span>
                                <span class="ml-1 text-xs text-fg-subtle">{{ row.part === 'open' ? 'ochiq' : 'yopiq' }}</span>
                            </template>
                            <template #cell-difficulty_level="{ value }">{{ value ?? 'istalgan' }}</template>
                            <template #cell-status="{ row }">
                                <Badge :variant="ruleStatus[row.status]" size="sm">
                                    <template v-if="row.status === 'ok'">Yetarli</template>
                                    <template v-else-if="row.status === 'fallback'">+{{ row.adjacent }} qo'shni qiyinlikdan</template>
                                    <template v-else>{{ row.shortfall - row.adjacent }} ta yetmaydi</template>
                                </Badge>
                            </template>
                        </Table>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <Button v-if="problemRules.length !== bank.rules.length" size="sm" variant="link" @click="showAllRules = !showAllRules">
                                {{ showAllRules ? 'Faqat muammoli qoidalar' : `Barcha qoidalar (${bank.rules.length})` }}
                            </Button>
                            <Button v-if="session.blueprint && !session.blueprint.trashed" size="sm" variant="ghost" :icon-left="Cog6ToothIcon"
                                :href="route('teacher.exam.blueprints.edit', session.blueprint.id)">Rejani ochish</Button>
                        </div>
                    </div>
                </Card>

                <!-- Variants + lifecycle -->
                <Card title="Variantlar">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Yaratilgan</dt>
                            <dd class="font-medium tabular-nums">{{ live.variants }} / {{ live.audience }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Qo'shni qiyinlik bilan</dt>
                            <dd class="font-medium tabular-nums">{{ readiness.variants_with_fallback }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Oxirgi generatsiya</dt>
                            <dd class="font-medium tabular-nums">{{ session.variants_generated_at ? dateTimeLabel(session.variants_generated_at) : '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Davomiylik</dt>
                            <dd class="font-medium tabular-nums">{{ session.duration_min }} daqiqa</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-fg-subtle">Variantlar boshlanishdan oldin avtomatik yaratiladi; kech qo'shilgan o'quvchiga boshlaganda yaratiladi.</p>
                    <template #footer>
                        <div class="flex flex-wrap justify-end gap-2">
                            <Button v-if="['draft', 'scheduled', 'open'].includes(live.status)" size="sm" variant="secondary"
                                :loading="busy === 'teacher.exam.sessions.variants'" @click="post('teacher.exam.sessions.variants')">
                                Variantlarni yaratish
                            </Button>
                            <Button v-if="['draft', 'scheduled', 'open'].includes(live.status) && !isPlacement" size="sm" variant="ghost"
                                :icon-left="XMarkIcon" @click="confirm = 'cancel'">Bekor qilish</Button>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Paid exams: price and who got in how -->
            <Card v-if="payments && !isPlacement" title="To'lov" :description="payments.price ? `Narxi: ${money(payments.price)} so'm` : 'Bepul imtihon'">
                <template #actions>
                    <BanknotesIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                </template>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-fg-muted">To'lagan</dt>
                        <dd class="text-lg font-semibold tabular-nums text-fg">{{ payments.purchased }}</dd>
                    </div>
                    <div>
                        <dt class="text-fg-muted">Kirdi: to'lov bilan</dt>
                        <dd class="text-lg font-semibold tabular-nums text-fg">{{ payments.via.purchase }}</dd>
                    </div>
                    <div>
                        <dt class="text-fg-muted">Kirdi: obuna bilan</dt>
                        <dd class="text-lg font-semibold tabular-nums text-fg">{{ payments.via.subscription }}</dd>
                    </div>
                    <div>
                        <dt class="text-fg-muted">Kirdi: bepul</dt>
                        <dd class="text-lg font-semibold tabular-nums text-fg">{{ payments.via.free }}</dd>
                    </div>
                </dl>
                <Alert v-if="payments.refund_pending || payments.refunded" variant="warning" class="mt-4">
                    Qaytarish: {{ payments.refund_pending }} ta kutilmoqda, {{ payments.refunded }} ta qaytarilgan. Pulni administrator qaytaradi.
                </Alert>
            </Card>

            <!-- Results -->
            <Card padding="none" title="Natijalar" :description="results.length ? `${results.length} ta urinish` : null">
                <template v-if="results.length" #actions>
                    <Button size="sm" variant="secondary" :icon-left="ArrowDownTrayIcon" :href="route('teacher.exam.sessions.export', session.id)" external>CSV yuklab olish</Button>
                </template>
                <Table :columns="resultColumns" :rows="results" :bordered="false" caption="Natijalar"
                    empty-title="Hali hech kim boshlamagan" :empty-description="isOpen ? 'O\'quvchilar boshlashi bilan shu yerda ko\'rinadi.' : ''">
                    <template #cell-rank="{ value }">{{ value ?? '—' }}</template>
                    <template #cell-student="{ row }">
                        <span class="font-medium text-fg">{{ row.student }}</span>
                    </template>
                    <template #cell-score_percent="{ row }">
                        <span class="font-semibold">{{ Math.round(row.score_percent) }}%</span>
                        <span class="block text-xs text-fg-subtle">{{ num(row.score) }}/{{ num(row.max_score) }}</span>
                    </template>
                    <template #cell-counts="{ row }">
                        <span class="tabular-nums"><span class="text-success">{{ row.correct_count }}</span> / <span class="text-danger">{{ row.wrong_count }}</span> / {{ row.skipped_count }}</span>
                    </template>
                    <template #cell-violations_count="{ value }">
                        <span :class="value > 0 ? 'text-warning' : 'text-fg-subtle'">{{ value }}</span>
                    </template>
                    <template #cell-status_label="{ row }">
                        <Badge :variant="row.status === 'in_progress' ? 'info' : row.status === 'invalidated' ? 'danger' : 'neutral'" size="sm">{{ row.status_label }}</Badge>
                    </template>
                    <template #cell-actions="{ row }">
                        <Button v-if="row.status === 'graded'" size="sm" variant="ghost" :loading="busy === row.id" @click="retake(row)">Qayta topshirishga ruxsat</Button>
                    </template>
                </Table>
            </Card>
        </div>

        <Modal :show="!!confirm" size="sm" :title="confirm ? CONFIRM[confirm].title : ''" :description="confirm ? CONFIRM[confirm].text : ''" @close="confirm = null">
            <template #footer>
                <Button variant="secondary" @click="confirm = null">Ortga</Button>
                <Button :variant="confirm ? CONFIRM[confirm].variant : 'primary'" @click="confirmAction">{{ confirm ? CONFIRM[confirm].button : '' }}</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
