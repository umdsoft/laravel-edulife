<template>
    <GuestLayout>

        <Head title="Kirish" />

        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-fg">Xush kelibsiz</h1>
            <p class="mt-1.5 text-sm text-fg-muted">Hisobingizga kiring va o'qishni davom ettiring</p>
        </div>

        <form class="space-y-5" novalidate @submit.prevent="submit">
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
