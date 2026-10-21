<script setup>
/**
 * Telegram/Register — registration inside the Telegram Mini App (TW, WebView-first).
 *
 * Who registers: a student, a parent registering the child (the child takes the exam through this
 * Telegram — the same as the bot's chat registration), or a parent who follows a child that has its
 * own account (a guardian account + a link request the teacher approves).
 * Fields are the website onboarding's (grade, subject, goal, school, the parent's phone + consent,
 * the public-name consent). The phone comes from Telegram.WebApp.requestContact() (Bot API 6.9;
 * the server verifies Telegram's signature) or is typed by hand (a typed phone never links an
 * existing account). Server: MiniAppRegistrationController.
 */
import { computed, onMounted, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { CheckCircleIcon, DevicePhoneMobileIcon, PhoneIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { Alert, Button, Card, Checkbox, Input, Select, Switch } from '@/Components/UI';
import ToastViewport from '@/Components/UI/ToastViewport.vue';
import ChoiceGroup from '@/Components/Exam/People/ChoiceGroup.vue';
import { getWebApp, haptic, useMainButton } from '@/Composables/useTelegram';

const props = defineProps({
    to: { type: String, default: null },
    subjects: { type: Array, default: () => [] },
    relations: { type: Array, default: () => [] },
    bot: { type: String, default: null },
    siteLink: { type: String, default: null },
});

const tg = ref(getWebApp());
const tgUser = computed(() => tg.value?.initDataUnsafe?.user || {});
const contactSupported = computed(() => {
    const app = tg.value;
    try {
        return !!app && typeof app.requestContact === 'function' && app.isVersionAtLeast('6.9');
    } catch {
        return false;
    }
});

const form = reactive({
    who: null, // student | parent
    parentMode: null, // parent_child | guardian
    grade: null,
    subject: null,
    goal: null,
    school_name: '',
    parent_phone: '+998',
    parent_consent: false,
    show_name_publicly: false,
    first_name: '',
    last_name: '',
    child_phone: '+998',
    relation: 'mother',
    consent: false,
    phone: '+998',
});
const errors = ref({});
const message = ref(null);
const sending = ref(false);

const phone = reactive({ masked: null, contact: '', sharing: false, manual: false, note: null });

const kind = computed(() => {
    if (form.who === 'student') return 'student';
    if (form.who === 'parent') return form.parentMode;
    return null;
});
const studentKind = computed(() => kind.value === 'student' || kind.value === 'parent_child');

const whoOptions = [
    { value: 'student', label: "O'quvchiman", hint: "O'zim imtihon topshiraman" },
    { value: 'parent', label: 'Ota-onaman', hint: 'Farzandim uchun' },
];
const parentOptions = [
    { value: 'parent_child', label: "Farzandimni ro'yxatdan o'tkazaman", hint: 'Farzandim imtihonni shu Telegram orqali topshiradi' },
    { value: 'guardian', label: 'Natijalarni kuzataman', hint: "Farzandimning o'z akkaunti bor (sayt yoki Telegram)" },
];
const gradeOptions = [
    { value: '9', label: '9-sinf' },
    { value: '10', label: '10-sinf' },
    { value: '11', label: '11-sinf' },
    { value: 'graduate', label: 'Bitirgan' },
];
const subjectOptions = computed(() => {
    const names = Object.fromEntries(props.subjects.map((s) => [s.code, s.name]));
    return [
        { value: 'math', label: names.math || 'Matematika' },
        { value: 'physics', label: names.physics || 'Fizika' },
        { value: 'both', label: 'Ikkalasi' },
    ];
});
const goalOptions = [
    { value: 'otm_grant', label: 'OTM grant', hint: "Oliy ta'limga grant asosida" },
    { value: 'milliy_sertifikat', label: 'Milliy sertifikat', hint: 'A / B / C daraja' },
    { value: 'sat', label: 'SAT', hint: 'Xorijiy universitetlar' },
];

const phoneLabel = computed(() => (kind.value === 'student' ? 'Telefon raqamingiz' : 'Sizning telefon raqamingiz (ota-ona)'));
const phoneHint = computed(() => (kind.value === 'student'
    ? "Saytga kirish uchun login bo'ladi. Saytda akkauntingiz bo'lsa — u Telegram'ga ulanadi."
    : 'Natijalar va hisobotlar shu raqam egasiga keladi.'));
const consentLabel = computed(() => (kind.value === 'parent_child'
    ? "Men — ota-onaman va farzandimning ma'lumotlarini platformada qayta ishlashga roziman"
    : "Ota-onam ma'lumotlarimni platformada qayta ishlashga rozi"));

// ---------------------------------------------------------------- server calls ---------------

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ init_data: tg.value?.initData || '', ...body }),
    });
    let data = {};
    try {
        data = await response.json();
    } catch {
        data = {};
    }
    return { status: response.status, ok: response.ok, data };
}

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function checkContact(contact) {
    // the signed requestContact response; without it, the contact message the bot received (webhook)
    for (let i = 0; i < (contact ? 1 : 5); i += 1) {
        const res = await postJson('/telegram/app/contact', contact ? { contact } : {});
        if (res.ok && res.data.verified) return res.data.phone;
        if (!res.ok) {
            phone.note = res.data?.errors?.phone?.[0] || res.data?.message || null;
            return null;
        }
        await wait(1200);
    }
    return null;
}

function shareContact() {
    const app = tg.value;
    phone.note = null;
    if (!contactSupported.value) {
        phone.manual = true;
        return;
    }
    phone.sharing = true;
    try {
        app.requestContact(async (sent, event) => {
            if (!sent) {
                phone.sharing = false;
                phone.note = "Raqam ulashilmadi. Qayta urinib ko'ring yoki qo'lda yozing.";
                return;
            }
            phone.contact = event?.response || '';
            const masked = await checkContact(phone.contact);
            phone.sharing = false;
            if (masked) {
                phone.masked = masked;
                phone.manual = false;
                errors.value = { ...errors.value, phone: null };
                haptic.notify('success');
            } else {
                phone.contact = '';
                phone.manual = true;
                phone.note = phone.note || "Telegram raqamni tasdiqlamadi. Raqamni qo'lda yozing.";
            }
        });
    } catch {
        phone.sharing = false;
        phone.manual = true;
    }
}

function payload() {
    const body = {
        kind: kind.value,
        to: props.to,
        contact: phone.masked ? phone.contact : '',
        phone: phone.masked ? null : form.phone,
        first_name: form.first_name || null,
        last_name: form.last_name || null,
    };
    if (studentKind.value) {
        Object.assign(body, {
            grade: form.grade,
            subject: form.subject,
            goal: form.goal,
            school_name: form.school_name || null,
            parent_phone: kind.value === 'student' ? form.parent_phone : null,
            parent_consent: form.parent_consent,
            show_name_publicly: form.show_name_publicly,
        });
    } else {
        const child = String(form.child_phone || '').replace(/\D/g, '');
        Object.assign(body, {
            child_phone: child.length > 3 ? form.child_phone : null,
            relation: form.relation,
            consent: form.consent,
        });
    }
    return body;
}

/** Ask once for permission to message the user (opened from a link, never wrote to the bot). */
function askWriteAccess() {
    const app = tg.value;
    return new Promise((resolve) => {
        try {
            if (!app || tgUser.value.allows_write_to_pm || !app.isVersionAtLeast('6.9') || typeof app.requestWriteAccess !== 'function') {
                resolve();
                return;
            }
            const timer = setTimeout(resolve, 15000);
            app.requestWriteAccess(() => {
                clearTimeout(timer);
                resolve();
            });
        } catch {
            resolve();
        }
    });
}

function go(data) {
    if (data.post && data.csrf_token) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = data.post;
        const t = document.createElement('input');
        t.type = 'hidden';
        t.name = '_token';
        t.value = data.csrf_token;
        f.appendChild(t);
        document.body.appendChild(f);
        f.submit();
        return;
    }
    window.location.replace(data.redirect || '/telegram/app');
}

async function submit() {
    if (sending.value) return;
    message.value = null;
    errors.value = {};
    if (!kind.value) {
        errors.value = { kind: form.who === 'parent' ? 'Qaysi holat sizga mosligini tanlang.' : "Kim ro'yxatdan o'tayotganini tanlang." };
        scrollToError();
        return;
    }
    sending.value = true;
    try {
        const res = await postJson('/telegram/app/register', payload());
        if (res.ok && res.data.redirect) {
            haptic.notify('success');
            await askWriteAccess();
            go(res.data);
            return;
        }
        haptic.notify('error');
        const e = res.data?.errors || {};
        errors.value = Object.fromEntries(Object.entries(e).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
        message.value = res.status === 422
            ? (Object.keys(errors.value).length > 1 ? "Ba'zi maydonlar to'ldirilmagan — belgilangan joylarni tekshiring." : res.data?.message)
            : (res.data?.message || "Xatolik yuz berdi. Qayta urinib ko'ring.");
        scrollToError();
    } catch {
        message.value = "Internet aloqasini tekshirib, qayta urinib ko'ring.";
    } finally {
        sending.value = false;
    }
}

function scrollToError() {
    setTimeout(() => {
        const el = document.querySelector('[aria-invalid="true"], [role="alert"]');
        el?.scrollIntoView?.({ block: 'center', behavior: 'smooth' });
    }, 50);
}

useMainButton(() => (tg.value ? { text: "Ro'yxatdan o'tish", onClick: submit, active: !!kind.value, progress: sending.value } : null));

onMounted(() => {
    form.first_name = tgUser.value.first_name || '';
    form.last_name = tgUser.value.last_name || '';
    // auth (agent L): from a website login link the phone was already shared on the confirm screen
    if (String(props.to || '').startsWith('login_')) {
        checkContact('').then((masked) => {
            if (masked && !phone.masked) phone.masked = masked;
        });
    }
});
</script>

<template>
    <Head title="Ro'yxatdan o'tish" />
    <div class="min-h-[var(--tg-app-height,100vh)] bg-bg text-fg"
        :style="{ paddingTop: 'var(--tg-safe-top, 0px)', paddingBottom: 'calc(1.5rem + var(--tg-safe-bottom, 0px))', paddingLeft: 'var(--tg-safe-left, 0px)', paddingRight: 'var(--tg-safe-right, 0px)' }"
        data-webview="telegram-register">
        <main id="main-content" class="mx-auto w-full max-w-xl px-4 py-5">
            <header class="mb-5">
                <p class="text-13 font-medium text-brand">Shanba imtihoni</p>
                <h1 class="mt-1 text-xl font-semibold text-fg">Ro'yxatdan o'tish</h1>
                <p class="mt-1 text-sm text-fg-muted">1 daqiqa. Keyin bepul qabul testi darajangizni aniqlaydi.</p>
            </header>

            <Alert v-if="!tg" variant="warning" class="mb-5" title="Telegram ichida oching">
                Bu sahifa Telegram bot ichida ishlaydi: botga o'ting va «Ochish» tugmasini bosing.
                <a v-if="siteLink" :href="siteLink" class="font-medium text-brand underline">Saytda kirish</a>
            </Alert>

            <form class="space-y-4" novalidate @submit.prevent="submit">
                <Card>
                    <div class="space-y-5">
                        <ChoiceGroup v-model="form.who" name="who" label="Kim ro'yxatdan o'tyapti?" :options="whoOptions"
                            :error="form.who ? null : errors.kind" />
                        <ChoiceGroup v-if="form.who === 'parent'" v-model="form.parentMode" name="parent_mode"
                            label="Qaysi biri sizga mos?" :options="parentOptions" :columns="1" :error="errors.kind" />
                    </div>
                </Card>

                <Card v-if="studentKind" :title="kind === 'parent_child' ? 'Farzandingiz haqida' : 'O\'qish'">
                    <div class="space-y-5">
                        <ChoiceGroup v-model="form.grade" name="grade" label="Sinf" :options="gradeOptions" :columns="4" :error="errors.grade" />
                        <ChoiceGroup v-model="form.subject" name="subject" label="Fan" :options="subjectOptions" :columns="3" :error="errors.subject" />
                        <ChoiceGroup v-model="form.goal" name="goal" label="Maqsad" :options="goalOptions" :columns="1" :error="errors.goal" />
                        <Input v-model="form.school_name" label="Maktab" name="school_name" optional maxlength="150"
                            placeholder="Masalan: 14-maktab, Urganch" autocomplete="off" hint="Maktablar reytingi uchun" :error="errors.school_name" />
                    </div>
                </Card>

                <Card v-if="kind" title="Telefon raqam">
                    <div class="space-y-4">
                        <div v-if="kind === 'guardian'" class="grid grid-cols-2 gap-3">
                            <Input v-model="form.first_name" label="Ism" name="first_name" maxlength="100" autocomplete="given-name" :error="errors.first_name" />
                            <Input v-model="form.last_name" label="Familiya" name="last_name" optional maxlength="100" autocomplete="family-name" :error="errors.last_name" />
                        </div>

                        <div v-if="phone.masked" class="flex items-center gap-3 rounded-md border border-success/40 bg-success/10 p-3" role="status">
                            <CheckCircleIcon class="h-6 w-6 shrink-0 text-success" aria-hidden="true" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-fg tabular-nums">{{ phone.masked }}</p>
                                <p class="text-xs text-fg-muted">Telegram tasdiqladi</p>
                            </div>
                        </div>
                        <template v-else>
                            <div v-if="contactSupported && !phone.manual" class="space-y-2">
                                <Button block size="lg" :icon-left="DevicePhoneMobileIcon" :loading="phone.sharing" @click="shareContact">
                                    Telefon raqamni ulashish
                                </Button>
                                <p class="text-xs text-fg-subtle">{{ phoneHint }}</p>
                                <button type="button" class="focus-ring rounded text-13 font-medium text-brand hover:underline" @click="phone.manual = true">
                                    Raqamni qo'lda yozaman
                                </button>
                            </div>
                            <Input v-else v-model="form.phone" type="tel" inputmode="tel" name="phone" :label="phoneLabel"
                                placeholder="+998901234567" autocomplete="tel" :leading-icon="PhoneIcon" required :hint="phoneHint" :error="errors.phone" />
                            <p v-if="phone.note" class="text-13 text-warning" role="alert">{{ phone.note }}</p>
                            <p v-if="errors.phone && !phone.manual && contactSupported" class="text-13 text-danger" role="alert">{{ errors.phone }}</p>
                        </template>
                    </div>
                </Card>

                <Card v-if="kind === 'guardian'" title="Farzandingiz" description="Farzandingiz ro'yxatdan o'tgan telefon raqami. Keyinroq ham qo'shish mumkin.">
                    <div class="space-y-4">
                        <Input v-model="form.child_phone" type="tel" inputmode="tel" name="child_phone" label="Farzandning telefon raqami" optional
                            placeholder="+998901234567" :leading-icon="PhoneIcon" :error="errors.child_phone" />
                        <Select v-model="form.relation" name="relation" label="Siz farzandga kimsiz?" :options="relations" :error="errors.relation" />
                        <Checkbox v-model="form.consent" name="consent" label="Farzandimning ma'lumotlarini qayta ishlashga roziman"
                            description="Natijalar faqat sizga va ustozga ko'rinadi. Bog'lanishni ustoz tasdiqlaydi." :error="errors.consent" />
                    </div>
                </Card>

                <Card v-if="studentKind" title="Ota-ona" :description="kind === 'student' ? 'Haftalik hisobot shu raqam egasiga bog\'lanadi.' : null">
                    <div class="space-y-5">
                        <Input v-if="kind === 'student'" v-model="form.parent_phone" type="tel" inputmode="tel" name="parent_phone"
                            label="Ota-onaning telefon raqami" placeholder="+998901234567" autocomplete="off"
                            :leading-icon="PhoneIcon" required :error="errors.parent_phone" />
                        <Checkbox v-model="form.parent_consent" name="parent_consent" :label="consentLabel"
                            description="Ism, sinf, natijalar faqat o'quv jarayoni uchun ishlatiladi va uchinchi shaxslarga berilmaydi."
                            :error="errors.parent_consent" />
                        <Switch v-model="form.show_name_publicly" label="Reytingda ism ko'rinsin"
                            description="O'chiq bo'lsa, reytingda taxallus ko'rsatiladi." />
                    </div>
                </Card>

                <Alert v-if="message" variant="danger" role="alert">{{ message }}</Alert>

                <div v-if="!tg" class="flex justify-end">
                    <Button type="submit" size="lg" :loading="sending" :disabled="!kind">Ro'yxatdan o'tish</Button>
                </div>
                <p class="flex items-center gap-1.5 text-xs text-fg-subtle">
                    <ShieldCheckIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    Rozilik vaqti saqlanadi. Telefon raqam boshqalarga ko'rinmaydi.
                </p>
            </form>
        </main>
        <ToastViewport />
    </div>
</template>
