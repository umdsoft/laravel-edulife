<template>
    <GuestLayout>

        <Head title="Kirish" />

        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-fg">Xush kelibsiz</h1>
            <p class="mt-1.5 text-sm text-fg-muted">Hisobingizga kiring va o'qishni davom ettiring</p>
        </div>

        <!-- auth (agent L): the phone + Telegram bot code is the main way; email / password for staff and anyone without Telegram -->
        <Tabs v-model="tab" :tabs="tabs" variant="pills" fitted label="Kirish usuli" class="mb-6" />

        <TelegramPhoneLogin v-if="tab === 'phone'" mode="login" />

        <form v-else class="space-y-5" novalidate @submit.prevent="submit">
            <!-- Login (email or phone) -->
            <Input v-model="form.login" type="text" label="Email yoki telefon" name="login" autocomplete="username"
                placeholder="you@edulife.uz yoki +998901234567" :leading-icon="UserIcon" :error="form.errors.login"
                autofocus />

            <!-- Password -->
            <Input v-model="form.password" type="password" revealable label="Parol" name="password"
                autocomplete="current-password" placeholder="••••••••" :leading-icon="LockClosedIcon"
                :error="form.errors.password" />

            <!-- Remember me -->
            <Checkbox v-model="form.remember" name="remember" label="Meni eslab qol" />

            <Button type="submit" variant="primary" size="lg" block :loading="form.processing">
                Kirish
            </Button>
        </form>

        <p class="mt-8 text-center text-sm text-fg-muted">
            Hisobingiz yo'qmi?
            <Link href="/register" class="focus-ring rounded-sm font-medium text-brand hover:underline underline-offset-4">
                Ro'yxatdan o'tish
            </Link>
        </p>
    </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserIcon, LockClosedIcon } from '@heroicons/vue/20/solid';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import TelegramPhoneLogin from '@/Components/Auth/TelegramPhoneLogin.vue';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const tabs = [
    { key: 'phone', label: 'Telefon raqam' },
    { key: 'password', label: 'Email va parol' },
];
// a failed password login comes back with errors.login: stay on that tab (also ?tab=password)
const page = usePage();
const tab = ref(page.props.errors?.login || page.props.errors?.password || /[?&]tab=password/.test(page.url || '') ? 'password' : 'phone');

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
