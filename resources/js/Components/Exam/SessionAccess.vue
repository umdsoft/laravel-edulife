<script setup>
/**
 * Paid exams — how the student gets into one Saturday session (server: App\Services\Exam\ExamAccess):
 *   free          "Bepul"
 *   subscription  "Obuna bilan kirish ochiq"
 *   purchased     "To'langan"
 *   pay           "Narxi: 15 000 so'm" + Payme / Click + "yoki obuna oling"
 *
 * Website: POST payments.checkout → the server redirects the browser to Payme / Click and the provider
 * sends the student back to the exam page. Telegram WebView: the same POST as JSON returns the provider
 * URL, opened with Telegram.WebApp.openLink (the Mini App stays put); the exam page refreshes when the
 * app becomes visible again.
 *
 *   <SessionAccess :session="s" :payments="payments" />            badge + pay block
 *   <SessionAccess :session="s" :payments="payments" badge-only />  only the badge
 */
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { AcademicCapIcon, CheckBadgeIcon, LockClosedIcon } from '@heroicons/vue/20/solid';
import { Alert, Badge, Button } from '@/Components/UI';
import { useTelegram } from '@/Composables/useTelegram';

const props = defineProps({
    session: { type: Object, required: true },
    payments: { type: Object, default: () => ({}) },
    badgeOnly: { type: Boolean, default: false },
});

const PROVIDERS = { payme: 'Payme', click: 'Click' };

const tg = useTelegram();
const access = computed(() => props.session.access || { state: 'free', price: 0, can_buy: false });
const providers = computed(() => (props.payments?.providers || []).filter((p) => PROVIDERS[p]));
const groupDigits = (v) => String(Math.round(Number(v) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const price = computed(() => groupDigits(access.value.price));
// the pay block only before the student has an attempt
const showPay = computed(() => !props.badgeOnly && access.value.state === 'pay' && !props.session.attempt);

const BADGES = {
    free: { label: 'Bepul', variant: 'neutral', icon: null },
    subscription: { label: 'Obuna bilan kirish ochiq', variant: 'success', icon: AcademicCapIcon },
    purchased: { label: "To'langan", variant: 'success', icon: CheckBadgeIcon },
};
const badge = computed(() => (access.value.state === 'pay'
    ? { label: `${price.value} so'm`, variant: 'warning', icon: LockClosedIcon }
    : BADGES[access.value.state] || BADGES.free));

const busy = ref(null);
const error = ref('');
const opened = ref(false);

const pay = async (provider) => {
    if (busy.value || !providers.value.includes(provider)) return;
    error.value = '';
    busy.value = provider;
    const data = { product_type: 'exam_session', product_id: props.session.id, provider };

    if (tg.active.value) {
        try {
            const { data: res } = await axios.post(route('payments.checkout'), data, { headers: { Accept: 'application/json' } });
            if (res.url && tg.openLink(res.url)) {
                opened.value = true;
            } else if (res.redirect) {
                router.visit(res.redirect);
            }
        } catch (e) {
            const errors = e.response?.data?.errors;
            error.value = e.response?.data?.message || (errors && Object.values(errors)[0]?.[0]) || "To'lovni boshlab bo'lmadi.";
        } finally {
            busy.value = null;
        }
        return;
    }

    router.post(route('payments.checkout'), data, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            error.value = errors.checkout || errors.provider || errors.product_id || Object.values(errors)[0] || "To'lovni boshlab bo'lmadi.";
            busy.value = null;
        },
        // on success the browser leaves for Payme / Click: keep the spinner until then
        onCancel: () => (busy.value = null),
    });
};
</script>

<template>
    <Badge v-if="badgeOnly || !showPay" :variant="badge.variant" :icon="badge.icon" size="sm">{{ badge.label }}</Badge>

    <div v-else class="space-y-3 rounded-md border border-border bg-surface-2 p-3 sm:p-4" data-testid="exam-pay">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <p class="text-sm text-fg-muted">Narxi: <span class="text-base font-semibold tabular-nums text-fg">{{ price }} so'm</span></p>
            <Link v-if="payments.plans_url" :href="payments.plans_url" class="focus-ring rounded text-13 font-medium text-brand hover:underline">
                yoki obuna oling
            </Link>
        </div>

        <template v-if="access.can_buy">
            <div class="grid grid-cols-2 gap-2">
                <Button v-for="(name, key) in PROVIDERS" :key="key" block :variant="key === 'payme' ? 'primary' : 'secondary'"
                    :disabled="!providers.includes(key) || (busy !== null && busy !== key)" :loading="busy === key"
                    :aria-label="`${name} orqali ${price} so'm to'lash`" @click="pay(key)">
                    {{ name }}
                </Button>
            </div>
            <p v-if="!providers.length" class="text-13 text-fg-subtle">To'lov tizimlari hali ulanmagan — administrator bilan bog'laning.</p>
            <p v-else-if="!opened" class="text-13 text-fg-subtle">To'lovdan so'ng shu sahifaga qaytasiz va imtihonni boshlaysiz.</p>
        </template>
        <p v-else-if="access.buy_problem" class="text-13 text-fg-muted">{{ access.buy_problem }}</p>

        <Alert v-if="opened" variant="info">
            To'lov sahifasi ochildi. To'lagach Telegram'ga qayting — holat o'zi yangilanadi.
        </Alert>
        <Alert v-if="error" variant="danger">{{ error }}</Alert>
    </div>
</template>
