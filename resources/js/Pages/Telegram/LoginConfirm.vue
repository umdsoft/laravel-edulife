<script setup>
/**
 * Telegram/LoginConfirm — the Mini App side of the website login (agent L, docs/auth/TELEGRAM_LOGIN.md §3).
 *
 * Opened by the bot's web_app button after /start login_<token>. There is never a login form here:
 * the page posts initData (and, if Telegram asks, the requestContact response) to /telegram/app/login.
 *   confirmed     the waiting browser logs in; "Saytda kirish tasdiqlandi" + continue in the app
 *   need_contact  one button: share the phone (Telegram.WebApp.requestContact, verified on the server)
 *   register      a new phone: the Mini App registration (it confirms the browser when it is done)
 *   refused / expired  the reason
 */
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { CheckCircleIcon, ComputerDesktopIcon, DevicePhoneMobileIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { Alert, Button, Card, Spinner } from '@/Components/UI';
import { getWebApp, haptic } from '@/Composables/useTelegram';

const props = defineProps({
    token: { type: String, default: null },
});

const tg = ref(getWebApp());
const state = ref('loading'); // loading | need_contact | confirmed | refused | expired | error
const message = ref('');
const note = ref('');
const redirect = ref(null);
const sharing = ref(false);

const contactSupported = computed(() => {
    try {
        return !!tg.value && typeof tg.value.requestContact === 'function' && tg.value.isVersionAtLeast('6.9');
    } catch {
        return false;
    }
});

async function confirm(contact = '') {
    if (!props.token) {
        state.value = 'expired';
        message.value = "Kirish havolasi noto'g'ri. Saytda «Telegram orqali kirish» ni qayta bosing.";
        return;
    }
    let res;
    let data = {};
    try {
        res = await fetch('/telegram/app/login', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ init_data: tg.value?.initData || '', token: props.token, contact: contact || null }),
        });
        data = await res.json();
    } catch {
        state.value = 'error';
        message.value = "Xatolik yuz berdi. Qayta urinib ko'ring.";
        return;
    }
    if (data.status === 'register' && data.register) {
        window.location.replace(data.register);
        return;
    }
    if (data.status === 'confirmed') {
        state.value = 'confirmed';
        redirect.value = data.redirect || '/telegram/app';
        haptic.notify('success');
        return;
    }
    if (data.status === 'need_contact') {
        state.value = 'need_contact';
        note.value = data.message || '';
        return;
    }
    state.value = res?.status === 401 ? 'error' : data.status || 'error';
    message.value = data.message || "Xatolik yuz berdi. Qayta urinib ko'ring.";
    haptic.notify('error');
}

function share() {
    note.value = '';
    if (!contactSupported.value) {
        note.value = "Telegram ilovangiz eski: bot chatidagi «Ilova ochilmayaptimi?» tugmasini bosing.";
        return;
    }
    sharing.value = true;
    try {
        tg.value.requestContact((sent, event) => {
            sharing.value = false;
            if (!sent) {
                note.value = "Raqam ulashilmadi. Saytga kirish uchun raqamni tasdiqlash kerak.";
                return;
            }
            state.value = 'loading';
            confirm(event?.response || '');
        });
    } catch {
        sharing.value = false;
        note.value = "Raqamni ulashib bo'lmadi. Qayta urinib ko'ring.";
    }
}

function continueInApp() {
    window.location.replace(redirect.value || '/telegram/app');
}

onMounted(() => {
    if (!tg.value || !tg.value.initData) {
        state.value = 'error';
        message.value = "Bu sahifa Telegram ichida ochiladi: bot xabaridagi tugmani bosing.";
        return;
    }
    try {
        tg.value.ready();
        tg.value.expand();
    } catch {
        /* older clients */
    }
    confirm();
});
</script>

<template>
    <Head title="Saytga kirish" />
    <div class="min-h-[var(--tg-app-height,100vh)] bg-bg text-fg"
        :style="{ paddingTop: 'var(--tg-safe-top, 0px)', paddingBottom: 'calc(1.5rem + var(--tg-safe-bottom, 0px))', paddingLeft: 'var(--tg-safe-left, 0px)', paddingRight: 'var(--tg-safe-right, 0px)' }"
        data-webview="telegram-login" :data-state="state">
        <main id="main-content" class="mx-auto w-full max-w-xl px-4 py-6">
            <header class="mb-5">
                <p class="text-13 font-medium text-brand">Shanba imtihoni</p>
                <h1 class="mt-1 text-xl font-semibold text-fg">Saytga kirish</h1>
            </header>

            <Card v-if="state === 'loading'">
                <div class="flex items-center gap-3 py-2 text-sm text-fg-muted" role="status">
                    <Spinner /> Tekshirilmoqda…
                </div>
            </Card>

            <Card v-else-if="state === 'need_contact'">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <DevicePhoneMobileIcon class="mt-0.5 h-6 w-6 shrink-0 text-brand" aria-hidden="true" />
                        <div>
                            <p class="font-medium text-fg">Telefon raqamingizni tasdiqlang</p>
                            <p class="mt-1 text-sm text-fg-muted">
                                Saytdagi akkauntingiz shu raqam bilan topiladi va Telegram'ga ulanadi. Akkaunt bo'lmasa — shu yerda
                                1 daqiqada ro'yxatdan o'tasiz.
                            </p>
                        </div>
                    </div>
                    <Button variant="primary" size="lg" block :loading="sharing" data-action="share-contact" @click="share">
                        Raqamni ulashish
                    </Button>
                    <p v-if="note" class="text-sm text-danger" role="alert">{{ note }}</p>
                </div>
            </Card>

            <Card v-else-if="state === 'confirmed'">
                <div class="space-y-4 text-center">
                    <CheckCircleIcon class="mx-auto h-12 w-12 text-success" aria-hidden="true" />
                    <div>
                        <p class="text-lg font-semibold text-fg">Saytda kirish tasdiqlandi</p>
                        <p class="mt-1 flex items-center justify-center gap-1.5 text-sm text-fg-muted">
                            <ComputerDesktopIcon class="h-4 w-4" aria-hidden="true" />
                            Brauzerga qayting — sahifa o'zi ochiladi.
                        </p>
                    </div>
                    <p class="text-sm text-fg-muted">Sayt va ilova — bitta akkaunt: imtihonlar, battle va natijalar ikkalasida bir xil.</p>
                    <Button variant="secondary" size="lg" block @click="continueInApp">Ilovada davom etish</Button>
                </div>
            </Card>

            <Alert v-else variant="warning" :title="state === 'expired' ? 'Havola eskirgan' : 'Tasdiqlab bo\'lmadi'">
                <span class="inline-flex items-start gap-2">
                    <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ message }}
                </span>
            </Alert>
        </main>
    </div>
</template>
