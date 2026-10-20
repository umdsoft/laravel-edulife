<script setup>
/**
 * The page Payme / Click send the student back to. The provider callback may arrive a few
 * seconds after the student, so a pending order is re-checked for a short while.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, XCircleIcon, ArrowPathIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Button, Card, PageHeader, Spinner } from '@/Components/UI';

const props = defineProps({
    order: { type: Object, required: true },
    nextUrl: { type: String, required: true },
    nextLabel: { type: String, required: true },
});

const STATES = {
    paid: { icon: CheckCircleIcon, tone: 'bg-success-50 text-success', title: "To'lov qabul qilindi", text: 'Xaridingiz faollashtirildi. Rahmat!' },
    processing: { icon: ClockIcon, tone: 'bg-warning-50 text-warning', title: "To'lov tekshirilmoqda", text: "To'lov tizimidan tasdiq kutilmoqda. Bu odatda bir necha soniya davom etadi." },
    pending: { icon: ClockIcon, tone: 'bg-warning-50 text-warning', title: "To'lov kutilmoqda", text: "To'lov hali amalga oshmadi. To'lovni yakunlamagan bo'lsangiz, qayta urinib ko'ring." },
    cancelled: { icon: XCircleIcon, tone: 'bg-danger-50 text-danger', title: "To'lov bekor qilindi", text: "Hisobingizdan pul yechilmadi. Qayta urinib ko'rishingiz mumkin." },
    refunded: { icon: ArrowUturnLeftIcon, tone: 'bg-surface-2 text-fg-muted', title: "To'lov qaytarildi", text: "Pul kartangizga qaytarildi, xarid bekor qilindi." },
};
const PROVIDERS = { payme: 'Payme', click: 'Click', manual: 'Promokod' };

const state = computed(() => STATES[props.order.status] || STATES.pending);
const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

// poll while the callback has not arrived yet (every 3 s, up to ~1 minute)
const polls = ref(0);
const MAX_POLLS = 20;
const waiting = computed(() => ['pending', 'processing'].includes(props.order.status) && polls.value < MAX_POLLS);
let timer = null;
const schedule = () => {
    clearTimeout(timer);
    if (!waiting.value) return;
    timer = setTimeout(() => {
        polls.value += 1;
        router.reload({ only: ['order', 'nextUrl', 'nextLabel'], onFinish: schedule });
    }, 3000);
};
onMounted(schedule);
onBeforeUnmount(() => clearTimeout(timer));

const refresh = () => {
    polls.value = 0;
    router.reload({ only: ['order', 'nextUrl', 'nextLabel'], onFinish: schedule });
};
</script>

<template>
    <StudentLayout title="To'lov holati">
        <Head title="To'lov holati" />

        <PageHeader title="To'lov holati" :description="`Buyurtma №${order.id}`" />

        <div class="max-w-xl space-y-6">
            <Card>
                <div class="flex flex-col items-center gap-3 py-4 text-center" role="status" aria-live="polite">
                    <span :class="['flex h-14 w-14 items-center justify-center rounded-full', state.tone]">
                        <component :is="state.icon" class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <h2 class="text-lg font-semibold text-fg">{{ state.title }}</h2>
                    <p class="max-w-sm text-sm text-fg-muted">{{ state.text }}</p>
                    <p v-if="order.status === 'paid' && order.coins" class="text-sm font-medium text-success">
                        +{{ groupDigits(order.coins) }} coin hisobingizga qo'shildi
                    </p>
                    <p v-if="waiting" class="flex items-center gap-2 text-13 text-fg-subtle">
                        <Spinner size="h-4 w-4" label="Tekshirilmoqda" /> Holat avtomatik yangilanadi…
                    </p>
                </div>

                <dl class="mt-2 divide-y divide-border rounded-md border border-border px-3 text-sm">
                    <div class="flex justify-between gap-3 py-2.5">
                        <dt class="text-fg-muted">Xarid</dt>
                        <dd class="text-right font-medium text-fg">{{ order.title }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-2.5">
                        <dt class="text-fg-muted">To'lov tizimi</dt>
                        <dd class="text-fg">{{ PROVIDERS[order.provider] || order.provider }}</dd>
                    </div>
                    <div v-if="order.discount_amount > 0" class="flex justify-between gap-3 py-2.5">
                        <dt class="text-success">Chegirma</dt>
                        <dd class="tabular-nums text-success">−{{ groupDigits(order.discount_amount) }} so'm</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-2.5 font-semibold">
                        <dt class="text-fg">Summa</dt>
                        <dd class="tabular-nums text-fg">{{ groupDigits(order.amount) }} so'm</dd>
                    </div>
                    <div class="flex justify-between gap-3 py-2.5">
                        <dt class="text-fg-muted">{{ order.paid_at ? "To'langan" : 'Yaratilgan' }}</dt>
                        <dd class="tabular-nums text-fg-muted">{{ order.paid_at || order.created_at }}</dd>
                    </div>
                </dl>

                <template #footer>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button v-if="!waiting && ['pending', 'processing'].includes(order.status)" variant="ghost" :icon-left="ArrowPathIcon" @click="refresh">
                            Holatni yangilash
                        </Button>
                        <Button v-if="order.can_retry && order.status !== 'processing'" variant="secondary"
                            :href="route('payments.pay', order.uuid)">Qayta to'lash</Button>
                        <Button :variant="order.status === 'paid' ? 'primary' : 'secondary'" :href="nextUrl">{{ nextLabel }}</Button>
                    </div>
                </template>
            </Card>

            <Alert v-if="order.status === 'processing' && !waiting" variant="info">
                Pul yechilgan bo'lsa-yu, holat o'zgarmasa, bir necha daqiqadan so'ng sahifani yangilang yoki
                buyurtma raqamini (№{{ order.id }}) ko'rsatib yordam xizmatiga yozing.
            </Alert>
        </div>
    </StudentLayout>
</template>
