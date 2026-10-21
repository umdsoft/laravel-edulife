<script setup>
/**
 * TelegramPhoneLogin — website login / registration through the Telegram bot (agent L, docs/auth/TELEGRAM_LOGIN.md).
 *
 *  phone  -> POST /login/phone            the bot sends a 6-digit code (the answer is the same for any phone)
 *  code   -> POST /login/phone/verify     logs in; or "Ha, bu men" in Telegram (the page polls the status)
 *  link   -> POST /login/telegram         t.me/<bot>?start=login_<token> + QR; the Mini App confirms, the page polls
 *  status -> POST /login/telegram/status  every 2.5 s while a code or a link is waiting
 *
 * mode="register": the role (student / guardian) is chosen first and the Telegram link is the main way
 * (a new phone has no bot chat yet); the code field stays for phones already linked to the bot.
 */
import { computed, onBeforeUnmount, ref } from 'vue';
import axios from 'axios';
import { ArrowTopRightOnSquareIcon, ChatBubbleLeftRightIcon, PhoneIcon, ShieldCheckIcon } from '@heroicons/vue/20/solid';
import { Alert, Button, Checkbox, Input, Spinner } from '@/Components/UI';

const props = defineProps({
    mode: { type: String, default: 'login', validator: (v) => ['login', 'register'].includes(v) },
});

const register = computed(() => props.mode === 'register');
const step = ref('phone'); // phone | code
const phone = ref('+998 ');
const remember = ref(false);
const role = ref('student');
const code = ref('');
const errors = ref({});
const busy = ref(false);
const sentMessage = ref('');
const hint = ref('');
const notice = ref('');
const resendIn = ref(0);

const link = ref(null); // { link, qr, dev_link, expires_in }
const linkBusy = ref(false);
const linkState = ref('idle'); // idle | waiting | expired | refused
const linkMessage = ref('');
const linkLeft = ref(0);

let poll = null;
let tick = null;

const roleOptions = [
    { value: 'student', label: "O'quvchiman" },
    { value: 'guardian', label: 'Ota-onaman' },
];

const linkLeftLabel = computed(() => {
    const m = Math.floor(linkLeft.value / 60);
    const s = String(linkLeft.value % 60).padStart(2, '0');
    return `${m}:${s}`;
});

function failure(error, field) {
    const res = error?.response;
    if (res?.status === 422 && res.data?.errors) {
        errors.value = Object.fromEntries(Object.entries(res.data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
        return;
    }
    if (res?.status === 419) {
        errors.value = { [field]: "Sahifa eskirdi. Sahifani yangilang va qayta urinib ko'ring." };
        return;
    }
    errors.value = { [field]: res?.data?.message || "Xatolik yuz berdi. Qayta urinib ko'ring." };
}

function startTimers() {
    stopTimers();
    poll = setInterval(checkStatus, 2500);
    tick = setInterval(() => {
        if (resendIn.value > 0) resendIn.value -= 1;
        if (linkState.value === 'waiting') {
            linkLeft.value = Math.max(0, linkLeft.value - 1);
            if (linkLeft.value === 0) linkState.value = 'expired';
        }
    }, 1000);
}

function stopTimers() {
    if (poll) clearInterval(poll);
    if (tick) clearInterval(tick);
    poll = null;
    tick = null;
}

async function sendCode() {
    if (busy.value) return;
    errors.value = {};
    notice.value = '';
    busy.value = true;
    try {
        const { data } = await axios.post('/login/phone', { phone: phone.value, remember: remember.value });
        sentMessage.value = data.message;
        hint.value = data.hint;
        resendIn.value = data.resend_in || 60;
        code.value = '';
        step.value = 'code';
        startTimers();
        if (register.value && !link.value) startLink();
    } catch (e) {
        failure(e, 'phone');
    } finally {
        busy.value = false;
    }
}

async function verify() {
    if (busy.value) return;
    errors.value = {};
    busy.value = true;
    try {
        const { data } = await axios.post('/login/phone/verify', { code: code.value });
        done(data.redirect);
    } catch (e) {
        failure(e, 'code');
        busy.value = false;
    }
}

async function startLink() {
    if (linkBusy.value) return;
    linkBusy.value = true;
    linkMessage.value = '';
    try {
        const { data } = await axios.post('/login/telegram', { role: register.value ? role.value : null, remember: remember.value });
        link.value = data;
        linkState.value = 'waiting';
        linkLeft.value = data.expires_in || 600;
        startTimers();
    } catch (e) {
        linkState.value = 'refused';
        linkMessage.value = e?.response?.data?.message || "Havolani yaratib bo'lmadi. Qayta urinib ko'ring.";
    } finally {
        linkBusy.value = false;
    }
}

async function checkStatus() {
    try {
        const { data } = await axios.post('/login/telegram/status');
        if (data.status === 'ok' && data.redirect) {
            done(data.redirect);
            return;
        }
        if (data.link && linkState.value === 'waiting') {
            if (data.link.status === 'refused') {
                linkState.value = 'refused';
                linkMessage.value = data.link.message || "Kirish rad etildi.";
            } else if (['expired', 'cancelled', 'used'].includes(data.link.status)) {
                linkState.value = 'expired';
            }
        }
        if (data.code && step.value === 'code' && data.code.status === 'cancelled' && data.code.message) {
            notice.value = data.code.message;
        }
        if (step.value !== 'code' && linkState.value !== 'waiting') stopTimers();
    } catch (e) {
        if (e?.response?.status === 419) stopTimers();
    }
}

function done(url) {
    stopTimers();
    notice.value = '';
    window.location.assign(url || '/');
}

function changePhone() {
    step.value = 'phone';
    code.value = '';
    errors.value = {};
    notice.value = '';
    if (linkState.value !== 'waiting') stopTimers();
}

onBeforeUnmount(stopTimers);
</script>

<template>
    <div class="space-y-6" data-auth="telegram-phone">
        <!-- 1. phone -->
        <form v-if="step === 'phone'" class="space-y-5" novalidate @submit.prevent="sendCode">
            <div v-if="register" role="radiogroup" aria-label="Kim ro'yxatdan o'tyapti?" class="grid grid-cols-2 gap-2">
                <button v-for="o in roleOptions" :key="o.value" type="button" role="radio" :aria-checked="role === o.value"
                    :class="['focus-ring h-11 rounded-md border text-sm font-medium transition-colors',
                        role === o.value ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-border bg-surface text-fg-muted hover:text-fg']"
                    @click="role = o.value">
                    {{ o.label }}
                </button>
            </div>

            <Input v-model="phone" type="tel" label="Telefon raqam" name="phone" autocomplete="tel" inputmode="tel"
                placeholder="+998 90 123 45 67" :leading-icon="PhoneIcon" :error="errors.phone" autofocus
                :hint="register ? 'Parol kerak emas — kirish Telegram bot orqali.' : 'Kod Telegram botimizga keladi.'" />

            <Checkbox v-if="!register" v-model="remember" name="remember" label="Meni eslab qol" />

            <Button type="submit" variant="primary" size="lg" block :loading="busy">
                {{ register ? 'Davom etish' : 'Kod olish' }}
            </Button>
        </form>

        <!-- 2. code -->
        <form v-else class="space-y-4" novalidate @submit.prevent="verify">
            <Alert variant="info" :title="sentMessage">
                {{ hint }}
            </Alert>
            <Alert v-if="notice" variant="warning">{{ notice }}</Alert>

            <Input v-model="code" type="text" label="Telegram'dan kelgan kod" name="code" inputmode="numeric"
                autocomplete="one-time-code" maxlength="6" placeholder="123456" :leading-icon="ShieldCheckIcon"
                :error="errors.code" :autofocus="!register" />

            <Button type="submit" variant="primary" size="lg" block :loading="busy" :disabled="code.replace(/\D/g, '').length !== 6">
                Kirish
            </Button>

            <div class="flex items-center justify-between text-sm">
                <button type="button" class="focus-ring rounded-sm text-fg-muted hover:text-fg" @click="changePhone">
                    Raqamni o'zgartirish
                </button>
                <button type="button" class="focus-ring rounded-sm font-medium text-brand disabled:cursor-not-allowed disabled:text-fg-subtle"
                    :disabled="resendIn > 0 || busy" @click="sendCode">
                    {{ resendIn > 0 ? `Qayta yuborish (${resendIn})` : 'Qayta yuborish' }}
                </button>
            </div>
        </form>

        <!-- 3. Telegram deep link (a phone without a bot chat yet, or no code came) -->
        <div class="space-y-3">
            <div class="flex items-center gap-3 text-xs text-fg-subtle" aria-hidden="true">
                <span class="h-px flex-1 bg-border" /> yoki <span class="h-px flex-1 bg-border" />
            </div>

            <Button v-if="linkState !== 'waiting'" type="button" :variant="register && step === 'code' ? 'primary' : 'secondary'" size="lg" block
                :loading="linkBusy" :icon-left="ChatBubbleLeftRightIcon" data-action="telegram-link" @click="startLink">
                {{ register ? "Telegram orqali ro'yxatdan o'tish" : 'Telegram orqali kirish' }}
            </Button>
            <p v-if="linkState === 'expired'" class="text-sm text-fg-muted" role="status">Havola eskirdi — yangisini oling.</p>
            <Alert v-if="linkState === 'refused' && linkMessage" variant="warning">{{ linkMessage }}</Alert>

            <div v-if="linkState === 'waiting' && link" class="rounded-lg border border-border bg-surface-2 p-4" data-telegram-link>
                <p class="text-sm font-medium text-fg">Telegram'da botni oching va «Saytga kirishni tasdiqlash» ni bosing</p>
                <p class="mt-1 text-13 text-fg-muted">Akkauntingiz bo'lmasa, bot ichida 1 daqiqada ro'yxatdan o'tasiz. Bu sahifa o'zi kiradi.</p>

                <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                    <!-- QR for a phone next to the computer (server-made SVG of our own link) -->
                    <div v-if="link.qr" class="hidden shrink-0 rounded-md bg-white p-2 sm:block [&>svg]:h-36 [&>svg]:w-36"
                        aria-label="Telegram havolasi QR kodi" role="img" v-html="link.qr" />
                    <div class="w-full space-y-2">
                        <Button v-if="link.link" :href="link.link" external variant="primary" block :icon-left="ArrowTopRightOnSquareIcon">
                            Telegram'da ochish
                        </Button>
                        <a v-if="link.dev_link" :href="link.dev_link" target="_blank" rel="noopener" data-dev-link
                            class="focus-ring block rounded-md border border-dashed border-border-strong px-3 py-2 text-center text-13 font-medium text-fg-muted hover:text-fg">
                            Emulyatorda ochish (local)
                        </a>
                        <p class="flex items-center gap-2 text-13 text-fg-muted" role="status">
                            <Spinner /> Tasdiq kutilmoqda · {{ linkLeftLabel }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
