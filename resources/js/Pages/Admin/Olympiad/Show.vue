<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from 'vue';
import {
    Alert, Badge, Button, Card, Dropdown, DropdownItem, EmptyState, IconButton, Input, Modal, PageHeader, StatCard,
} from '@/Components/UI';
import {
    PencilSquareIcon, DocumentDuplicateIcon, SignalIcon, ClipboardDocumentCheckIcon, UsersIcon, CheckCircleIcon,
    ChartBarIcon, BanknotesIcon, TrophyIcon, ArrowDownTrayIcon, ChevronRightIcon, Squares2X2Icon, ArrowRightIcon,
    CalendarDaysIcon, EllipsisVerticalIcon, ArrowUturnLeftIcon, NoSymbolIcon, TrashIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    statistics: Object,
    // OlympiadService::managementActions: can_delete / can_revert / can_cancel / active_registrations
    actions: { type: Object, default: () => ({}) },
});

const STATUS = {
    draft: { label: 'Qoralama', variant: 'neutral' },
    upcoming: { label: 'Tez kunda', variant: 'info' },
    registration_open: { label: "Ro'yxat ochiq", variant: 'success' },
    registration_closed: { label: "Ro'yxat yopiq", variant: 'neutral' },
    live: { label: 'Jonli', variant: 'danger' },
    grading: { label: 'Baholanmoqda', variant: 'warning' },
    results_ready: { label: 'Natijalar tayyor', variant: 'success' },
    completed: { label: 'Yakunlangan', variant: 'brand' },
    cancelled: { label: 'Bekor qilingan', variant: 'neutral' },
};
const statusOf = (s) => STATUS[s] || STATUS.draft;

// Lifecycle shown as a stepper; transitions are the same ones the page always offered
const FLOW = ['draft', 'upcoming', 'registration_open', 'live', 'grading', 'completed'];
const NEXT = {
    draft: { status: 'upcoming', label: "E'lon qilish (tez kunda)", description: "Olimpiada e'lon qilinadi, lekin ro'yxat hali yopiq bo'ladi." },
    upcoming: { status: 'registration_open', label: "Ro'yxatni ochish", description: "Foydalanuvchilar olimpiadaga ro'yxatdan o'ta boshlaydi." },
    registration_open: { status: 'live', label: 'Jonli rejimga o\'tkazish', description: "Ishtirokchilar topshiriqlarni bajarishni boshlashi mumkin bo'ladi." },
    live: { status: 'grading', label: "Baholashga o'tkazish", description: 'Yangi urinishlar qabul qilinmaydi, javoblar baholash navbatiga tushadi.' },
    grading: { status: 'completed', label: 'Olimpiadani yakunlash', description: "Natijalar yakuniy deb belgilanadi." },
};

const currentStep = computed(() => {
    const i = FLOW.indexOf(props.olympiad.status);
    if (i >= 0) return i;
    if (props.olympiad.status === 'registration_closed') return 2;
    if (props.olympiad.status === 'results_ready') return 4;
    return 0;
});
const nextAction = computed(() => NEXT[props.olympiad.status] || null);

const isPaid = (v) => Number(v) > 0;
const money = (v) => `${Math.round(Number(v) || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} so'm`;
const priceLabel = (v) => (isPaid(v) ? money(v) : 'Bepul');

const stats = computed(() => props.statistics || {});
const completedCount = computed(() => stats.value.total_completed ?? stats.value.completed_attempts ?? 0);
const averageScore = computed(() => {
    const v = Number(stats.value.average_score);
    return Number.isFinite(v) ? Math.round(v * 10) / 10 : 0;
});
const registrationFee = computed(() => props.olympiad.pricing?.registration_fee ?? props.olympiad.schedule?.registration_fee ?? 0);
const sections = computed(() => props.olympiad.sections || []);
const totalPoints = computed(() => sections.value.reduce((s, x) => s + (Number(x.max_points) || 0), 0));
const totalMinutes = computed(() => sections.value.reduce((s, x) => s + (Number(x.duration_minutes) || 0), 0));

// Status change (confirmed in a modal)
const statusModal = ref(false);
const statusChanging = ref(false);
const changeStatus = () => {
    if (!nextAction.value) return;
    router.patch(route('admin.olympiads.status', props.olympiad.id), { status: nextAction.value.status }, {
        preserveScroll: true,
        onStart: () => (statusChanging.value = true),
        onFinish: () => {
            statusChanging.value = false;
            statusModal.value = false;
        },
    });
};

// Back to draft / cancel / delete — each confirmed in one modal
const MANAGE = {
    revert: {
        title: 'Qoralamaga qaytarish',
        body: "Olimpiada o'quvchilar ro'yxatidan yashiriladi va yana tahrirlanadigan qoralama bo'ladi. Ro'yxatdan o'tganlar yo'q.",
        confirm: 'Qoralamaga qaytarish',
        variant: 'primary',
    },
    cancel: {
        title: 'Olimpiadani bekor qilish',
        body: "Olimpiada o'quvchilarga ko'rinmaydi, barcha ro'yxatlar bekor qilinadi va coin bilan to'langan summalar qaytariladi. Bu amalni ortga qaytarib bo'lmaydi.",
        confirm: 'Bekor qilish',
        variant: 'danger',
    },
    delete: {
        title: "Olimpiadani o'chirish",
        body: "Olimpiada va uning bo'limlari o'chiriladi. Unda ro'yxatdan o'tganlar va urinishlar yo'q.",
        confirm: "O'chirish",
        variant: 'danger',
    },
};
const manageAction = ref(null);
const managing = ref(false);
const manage = computed(() => (manageAction.value ? MANAGE[manageAction.value] : null));
const hasManageActions = computed(() => props.actions?.can_revert || props.actions?.can_cancel || props.actions?.can_delete);
const runManage = () => {
    const opts = {
        preserveScroll: true,
        onStart: () => (managing.value = true),
        onFinish: () => {
            managing.value = false;
            manageAction.value = null;
        },
    };
    if (manageAction.value === 'delete') {
        router.delete(route('admin.olympiads.destroy', props.olympiad.id), opts);
    } else {
        router.patch(route('admin.olympiads.status', props.olympiad.id), { status: manageAction.value === 'revert' ? 'draft' : 'cancelled' }, opts);
    }
};

// Duplicate (posts to the existing duplicate endpoint)
const showDuplicateModal = ref(false);
const duplicateForm = useForm({
    title: '',
    olympiad_start_at: '',
    olympiad_end_at: '',
    registration_start_at: '',
    registration_end_at: '',
});
const openDuplicate = () => {
    duplicateForm.reset();
    duplicateForm.clearErrors();
    duplicateForm.title = `${props.olympiad.title} (nusxa)`;
    showDuplicateModal.value = true;
};
const submitDuplicate = () => {
    duplicateForm.post(route('admin.olympiads.duplicate', props.olympiad.id), {
        onSuccess: () => (showDuplicateModal.value = false),
    });
};

const breadcrumbs = computed(() => [
    { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
    { label: props.olympiad.title },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="olympiad.title" />

        <PageHeader :title="olympiad.title" :breadcrumbs="breadcrumbs" :description="olympiad.description || null">
            <template #meta>
                <Badge :variant="statusOf(olympiad.status).variant" dot>{{ statusOf(olympiad.status).label }}</Badge>
                <Badge v-if="olympiad.type?.display_name" variant="neutral">
                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: olympiad.type?.color || 'rgb(var(--brand-600))' }"
                        aria-hidden="true" />
                    {{ olympiad.type.display_name }}
                </Badge>
                <Badge v-if="olympiad.stage?.display_name" variant="neutral">{{ olympiad.stage.display_name }}</Badge>
            </template>
            <template #actions>
                <Button variant="secondary" :icon-left="PencilSquareIcon" :href="route('admin.olympiads.edit', olympiad.id)">
                    Tahrirlash
                </Button>
                <Button variant="secondary" :icon-left="DocumentDuplicateIcon" @click="openDuplicate">Nusxalash</Button>
                <Button v-if="olympiad.status === 'live'" :icon-left="SignalIcon" :href="route('admin.olympiads.monitor', olympiad.id)">
                    Jonli monitoring
                </Button>
                <Button v-else-if="['grading', 'completed'].includes(olympiad.status)" :icon-left="ClipboardDocumentCheckIcon"
                    :href="route('admin.olympiads.grading.index', olympiad.id)">
                    Baholash
                </Button>
                <Dropdown v-if="hasManageActions" align="right">
                    <template #trigger>
                        <IconButton :icon="EllipsisVerticalIcon" label="Boshqa amallar" variant="secondary" />
                    </template>
                    <DropdownItem v-if="actions.can_revert" :icon="ArrowUturnLeftIcon" @click="manageAction = 'revert'">
                        Qoralamaga qaytarish
                    </DropdownItem>
                    <DropdownItem v-if="actions.can_cancel" :icon="NoSymbolIcon" danger @click="manageAction = 'cancel'">
                        Bekor qilish
                    </DropdownItem>
                    <DropdownItem v-if="actions.can_delete" :icon="TrashIcon" danger @click="manageAction = 'delete'">
                        O'chirish
                    </DropdownItem>
                </Dropdown>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Key numbers -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Ro'yxatdagilar" :value="stats.total_registrations || 0" :icon="UsersIcon" tone="brand"
                    :hint="stats.confirmed_registrations != null ? `${stats.confirmed_registrations} tasdiqlangan` : null" />
                <StatCard label="Yakunlaganlar" :value="completedCount" :icon="CheckCircleIcon" tone="success"
                    :hint="stats.disqualified_count ? `${stats.disqualified_count} diskvalifikatsiya` : null" />
                <StatCard label="O'rtacha ball" :value="averageScore" unit="%" :icon="ChartBarIcon" tone="info" />
                <StatCard v-if="stats.total_revenue !== undefined" label="Daromad" :value="Math.round(stats.total_revenue || 0)"
                    unit="so'm" :icon="BanknotesIcon" tone="warning" />
                <StatCard v-else label="Eng yuqori ball" :value="stats.top_score != null ? Math.round(stats.top_score * 10) / 10 : '—'"
                    :icon="TrophyIcon" tone="warning" />
            </div>

            <!-- Lifecycle -->
            <Card title="Holat" description="Olimpiada bosqichlari ketma-ketligi">
                <ol class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="Olimpiada holati">
                    <li v-for="(step, i) in FLOW" :key="step" class="flex items-center gap-2.5"
                        :aria-current="i === currentStep ? 'step' : undefined">
                        <span :class="[
                            'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                            i < currentStep ? 'bg-brand-600 text-white' : '',
                            i === currentStep ? 'bg-brand-50 text-brand ring-2 ring-brand-500' : '',
                            i > currentStep ? 'bg-surface-2 text-fg-subtle' : '',
                        ]">
                            <CheckIcon v-if="i < currentStep" class="h-4 w-4" aria-hidden="true" />
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <span :class="['text-13', i === currentStep ? 'font-semibold text-fg' : 'text-fg-muted']">
                            {{ statusOf(step).label }}
                        </span>
                    </li>
                </ol>
                <template v-if="nextAction" #footer>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-13 text-fg-muted">
                            Keyingi qadam: <span class="font-medium text-fg">{{ nextAction.label }}</span>
                        </p>
                        <Button :icon-right="ArrowRightIcon" :variant="nextAction.status === 'live' ? 'danger' : 'primary'"
                            @click="statusModal = true">
                            {{ nextAction.label }}
                        </Button>
                    </div>
                </template>
            </Card>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <!-- Sections -->
                    <Card padding="none" title="Bo'limlar"
                        :description="sections.length ? `${sections.length} ta bo'lim · ${totalMinutes} daqiqa · ${totalPoints} ball` : null">
                        <template #actions>
                            <Button size="sm" variant="ghost" :href="route('admin.olympiads.edit', olympiad.id) + '#sections'">
                                Tahrirlash
                            </Button>
                        </template>
                        <ul v-if="sections.length" class="divide-y divide-border">
                            <li v-for="(section, index) in sections" :key="section.id" class="flex items-center gap-4 px-5 py-3.5">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-surface-2 text-13 font-semibold tabular-nums text-fg-muted">
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-fg">{{ section.title }}</p>
                                    <p class="text-13 text-fg-subtle">{{ section.section_type }}</p>
                                </div>
                                <div class="text-right text-13 tabular-nums">
                                    <p class="font-medium text-fg">{{ section.max_points }} ball</p>
                                    <p class="text-fg-subtle">{{ section.duration_minutes }} daqiqa</p>
                                </div>
                            </li>
                        </ul>
                        <EmptyState v-else :icon="Squares2X2Icon" title="Bo'limlar qo'shilmagan"
                            description="Tahrirlash sahifasida bo'limlarni qo'shing" size="sm" class="py-8" />
                    </Card>

                    <!-- Schedule -->
                    <Card title="Jadval">
                        <dl class="divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Ro'yxat boshlanishi</dt>
                                <dd class="font-medium tabular-nums">{{ olympiad.schedule?.registration_start_at || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Ro'yxat tugashi</dt>
                                <dd class="font-medium tabular-nums">{{ olympiad.schedule?.registration_end_at || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="flex items-center gap-1.5 text-fg">
                                    <CalendarDaysIcon class="h-4 w-4 text-brand" aria-hidden="true" />
                                    Olimpiada boshlanishi
                                </dt>
                                <dd class="font-semibold tabular-nums text-fg">{{ olympiad.schedule?.olympiad_start_at || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="flex items-center gap-1.5 text-fg">
                                    <CalendarDaysIcon class="h-4 w-4 text-brand" aria-hidden="true" />
                                    Olimpiada tugashi
                                </dt>
                                <dd class="font-semibold tabular-nums text-fg">{{ olympiad.schedule?.olympiad_end_at || '—' }}</dd>
                            </div>
                        </dl>
                    </Card>
                </div>

                <aside class="space-y-6">
                    <Card title="Ma'lumot">
                        <dl class="divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Turi</dt>
                                <dd class="text-right font-medium">{{ olympiad.type?.display_name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Bosqich</dt>
                                <dd class="text-right font-medium">{{ olympiad.stage?.display_name || '—' }}</dd>
                            </div>
                            <div v-if="olympiad.series?.name" class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Seriya</dt>
                                <dd class="text-right font-medium">{{ olympiad.series.name }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Ro'yxat narxi</dt>
                                <dd class="text-right font-medium tabular-nums">{{ priceLabel(registrationFee) }}</dd>
                            </div>
                            <div v-if="isPaid(olympiad.pricing?.demo_price)" class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Demo narxi</dt>
                                <dd class="text-right font-medium tabular-nums">{{ money(olympiad.pricing.demo_price) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Yaratuvchi</dt>
                                <dd class="text-right font-medium">{{ olympiad.created_by || '—' }}</dd>
                            </div>
                        </dl>
                    </Card>

                    <Card padding="none" title="Tezkor amallar">
                        <nav class="divide-y divide-border" aria-label="Tezkor amallar">
                            <Link :href="route('admin.olympiads.registrations', olympiad.id)"
                                class="focus-ring flex items-center gap-3 px-5 py-3 text-sm text-fg transition-colors hover:bg-surface-2">
                                <UsersIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                                <span class="flex-1">Ro'yxatdagilar</span>
                                <span class="tabular-nums text-fg-muted">{{ stats.total_registrations || 0 }}</span>
                                <ChevronRightIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                            </Link>
                            <Link :href="route('admin.olympiads.monitor.leaderboard', olympiad.id)"
                                class="focus-ring flex items-center gap-3 px-5 py-3 text-sm text-fg transition-colors hover:bg-surface-2">
                                <TrophyIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                                <span class="flex-1">Reyting</span>
                                <ChevronRightIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                            </Link>
                            <a :href="route('admin.olympiads.grading.export', olympiad.id)"
                                class="focus-ring flex items-center gap-3 px-5 py-3 text-sm text-fg transition-colors hover:bg-surface-2">
                                <ArrowDownTrayIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                                <span class="flex-1">Natijalarni CSV yuklab olish</span>
                            </a>
                        </nav>
                    </Card>
                </aside>
            </div>
        </div>

        <!-- Status change confirm -->
        <Modal :show="statusModal" size="md" :title="nextAction?.label || 'Holatni o\'zgartirish'" @close="statusModal = false">
            <div class="space-y-3 text-sm text-fg-muted">
                <p>{{ nextAction?.description }}</p>
                <p>
                    <span class="font-medium text-fg">{{ statusOf(olympiad.status).label }}</span>
                    →
                    <span class="font-medium text-fg">{{ statusOf(nextAction?.status).label }}</span>
                </p>
                <Alert v-if="nextAction?.status === 'live'" variant="warning">
                    Jonli rejimga o'tgach ishtirokchilar darhol topshiriqlarni ko'ra boshlaydi.
                </Alert>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="statusChanging" @click="statusModal = false">Bekor qilish</Button>
                    <Button :variant="nextAction?.status === 'live' ? 'danger' : 'primary'" :loading="statusChanging"
                        @click="changeStatus">
                        Tasdiqlash
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Back to draft / cancel / delete confirm -->
        <Modal :show="!!manage" size="md" :title="manage?.title" @close="manageAction = null">
            <div class="space-y-3 text-sm text-fg-muted">
                <p>{{ manage?.body }}</p>
                <p v-if="manageAction === 'cancel' && actions.active_registrations">
                    Ro'yxatdagilar: <span class="font-medium text-fg tabular-nums">{{ actions.active_registrations }}</span>
                </p>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="managing" @click="manageAction = null">Yopish</Button>
                    <Button :variant="manage?.variant" :loading="managing" @click="runManage">{{ manage?.confirm }}</Button>
                </div>
            </template>
        </Modal>

        <!-- Duplicate -->
        <Modal :show="showDuplicateModal" size="lg" title="Olimpiadani nusxalash"
            description="Bo'limlar va sozlamalar ko'chiriladi, yangi olimpiada qoralama holatida yaratiladi"
            @close="showDuplicateModal = false">
            <form id="duplicate-form" class="space-y-4" @submit.prevent="submitDuplicate">
                <Input v-model="duplicateForm.title" label="Yangi nomi" required :error="duplicateForm.errors.title" autofocus />
                <div class="grid gap-4 sm:grid-cols-2">
                    <Input v-model="duplicateForm.olympiad_start_at" type="datetime-local" label="Olimpiada boshlanishi" required
                        :error="duplicateForm.errors.olympiad_start_at" />
                    <Input v-model="duplicateForm.olympiad_end_at" type="datetime-local" label="Olimpiada tugashi" required
                        :error="duplicateForm.errors.olympiad_end_at" />
                    <Input v-model="duplicateForm.registration_start_at" type="datetime-local" label="Ro'yxat boshlanishi" optional
                        :error="duplicateForm.errors.registration_start_at" />
                    <Input v-model="duplicateForm.registration_end_at" type="datetime-local" label="Ro'yxat tugashi" optional
                        :error="duplicateForm.errors.registration_end_at" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="duplicateForm.processing" @click="showDuplicateModal = false">
                        Bekor qilish
                    </Button>
                    <Button type="submit" form="duplicate-form" :icon-left="DocumentDuplicateIcon" :loading="duplicateForm.processing">
                        Nusxa yaratish
                    </Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
