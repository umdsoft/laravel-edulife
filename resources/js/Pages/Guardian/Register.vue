<template>
    <GuestLayout>
        <Head title="Ota-ona sifatida ro'yxatdan o'tish" />

        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-fg">Ota-onalar uchun</h1>
            <p class="mt-1.5 text-sm text-fg-muted">Farzandingizning shanba imtihoni natijalari va yakshanba hisobotlarini kuzating</p>
        </div>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Input v-model="form.first_name" type="text" label="Ism" name="first_name" autocomplete="given-name"
                    placeholder="Ism" :error="form.errors.first_name" autofocus />
                <Input v-model="form.last_name" type="text" label="Familiya" name="last_name" autocomplete="family-name"
                    placeholder="Familiya" :error="form.errors.last_name" />
            </div>

            <Input v-model="form.phone" type="tel" label="Telefon raqamingiz" name="phone" autocomplete="tel" inputmode="tel"
                placeholder="+998901234567" :leading-icon="PhoneIcon" :error="form.errors.phone"
                hint="Farzandingiz anketada yozgan raqam bo'lsa, bog'lash tezroq tasdiqlanadi" />

            <Input v-model="form.password" type="password" revealable label="Parol" name="password"
                autocomplete="new-password" placeholder="••••••••" :leading-icon="LockClosedIcon" :error="form.errors.password" />
            <Input v-model="form.password_confirmation" type="password" revealable label="Parolni tasdiqlang"
                name="password_confirmation" autocomplete="new-password" placeholder="••••••••"
                :leading-icon="LockClosedIcon" :error="form.errors.password_confirmation" />

            <Button type="submit" variant="primary" size="lg" block :loading="form.processing">Ro'yxatdan o'tish</Button>
        </form>

        <p class="mt-8 text-center text-sm text-fg-muted">
            O'quvchimisiz?
            <Link href="/register" class="focus-ring rounded-sm font-medium text-brand underline-offset-4 hover:underline">O'quvchi sifatida</Link>
            ·
            <Link href="/login" class="focus-ring rounded-sm font-medium text-brand underline-offset-4 hover:underline">Kirish</Link>
        </p>
    </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PhoneIcon, LockClosedIcon } from '@heroicons/vue/20/solid';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/guardian/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
