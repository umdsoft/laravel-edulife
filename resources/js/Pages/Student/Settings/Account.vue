<template>
    <StudentLayout title="Sozlamalar">
        <Head title="Sozlamalar" />

        <PageHeader title="Sozlamalar" description="Hisob ma'lumotlari va xavfsizlik">
            <template #tabs>
                <Tabs :tabs="tabs" label="Sozlamalar bo'limlari" />
            </template>
        </PageHeader>

        <div class="max-w-xl space-y-6">
            <!-- Account -->
            <Card title="Hisob ma'lumotlari" description="Ismingiz va aloqa ma'lumotlari">
                <form id="account-form" class="space-y-5" @submit.prevent="updateAccount">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <Input v-model="form.first_name" label="Ism" required autocomplete="given-name"
                            :error="form.errors.first_name" />
                        <Input v-model="form.last_name" label="Familiya" required autocomplete="family-name"
                            :error="form.errors.last_name" />
                    </div>

                    <Input v-model="form.username" label="Foydalanuvchi nomi" optional autocomplete="username"
                        placeholder="faqat kichik harf, raqam va _" :leading-icon="AtSymbolIcon"
                        :hint="`Profilingiz: edulife.uz/u/${form.username || 'username'}`" :error="form.errors.username" />

                    <Input v-model="form.email" type="email" label="Email" disabled
                        hint="Emailni o'zgartirib bo'lmaydi" :error="form.errors.email" />

                    <Input v-model="form.phone" type="tel" label="Telefon" required autocomplete="tel" inputmode="tel"
                        placeholder="+998901234567" hint="Format: +998XXXXXXXXX" :error="form.errors.phone" />
                </form>
                <template #footer>
                    <div class="flex justify-end gap-2">
                        <Button variant="secondary" :disabled="!form.isDirty || form.processing" @click="form.reset()">Bekor qilish</Button>
                        <Button type="submit" form="account-form" :loading="form.processing">Saqlash</Button>
                    </div>
                </template>
            </Card>

            <!-- Password -->
            <Card title="Parolni o'zgartirish" description="Kamida 8 ta belgi">
                <form id="password-form" class="space-y-5" @submit.prevent="updatePassword">
                    <Input v-model="passwordForm.current_password" type="password" label="Joriy parol" required revealable
                        autocomplete="current-password" :error="passwordForm.errors.current_password" />
                    <Input v-model="passwordForm.password" type="password" label="Yangi parol" required revealable
                        autocomplete="new-password" :error="passwordForm.errors.password" />
                    <Input v-model="passwordForm.password_confirmation" type="password" label="Yangi parolni tasdiqlang"
                        required revealable autocomplete="new-password" :error="passwordForm.errors.password_confirmation" />
                </form>
                <template #footer>
                    <div class="flex justify-end gap-2">
                        <Button variant="secondary" :disabled="passwordForm.processing" @click="passwordForm.reset(); passwordForm.clearErrors()">Bekor qilish</Button>
                        <Button type="submit" form="password-form" :loading="passwordForm.processing">Parolni yangilash</Button>
                    </div>
                </template>
            </Card>

            <!-- TW (Telegram WebView-first): link this website account to the Telegram bot -->
            <Card v-if="telegramLink" title="Telegram bot" description="Imtihon eslatmalari, natijalar va battle — Telegram ichida">
                <p class="text-sm text-fg-muted">Tugmani bosing — Telegram ochiladi va akkauntingiz botga ulanadi (havola 15 daqiqa amal qiladi).</p>
                <template #footer>
                    <div class="flex justify-end">
                        <Button :href="telegramLink" external :icon-left="PaperAirplaneIcon">Telegramni ulash</Button>
                    </div>
                </template>
            </Card>
        </div>
    </StudentLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { AtSymbolIcon, LockClosedIcon, PaperAirplaneIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import { routePath } from '@/Utils/nav';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Button, Card, Input, PageHeader, Tabs } from '@/Components/UI';

const props = defineProps({
    user: Object,
});

// TW: GET /telegram/link -> t.me/<bot>?start=link_<token> (one-time, 15 minutes)
const telegramLink = computed(() => routePath('telegram.link'));

// Same fields / endpoints as before; useForm adds processing + validation errors
const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    username: props.user.username,
    email: props.user.email,
    phone: props.user.phone,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateAccount = () => {
    form.put(route('student.settings.account.update'), {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
    });
};

const updatePassword = () => {
    passwordForm.put(route('student.settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const tabs = computed(() => [
    { key: 'account', label: 'Hisob', icon: UserCircleIcon, href: route('student.settings.account'), active: true },
    ...(route().has('student.settings.privacy')
        ? [{ key: 'privacy', label: 'Maxfiylik', icon: LockClosedIcon, href: route('student.settings.privacy') }]
        : []),
]);
</script>
