<template>
    <GuestLayout>

        <Head title="Ro'yxatdan o'tish" />

        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-fg">Ro'yxatdan o'tish</h1>
            <p class="mt-1.5 text-sm text-fg-muted">Yangi hisob yarating va o'qishni boshlang</p>
        </div>

        <form class="space-y-5" novalidate @submit.prevent="submit">
            <!-- Name -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Input v-model="form.first_name" type="text" label="Ism" name="first_name" autocomplete="given-name"
                    placeholder="Ism" :error="form.errors.first_name" autofocus />

                <Input v-model="form.last_name" type="text" label="Familiya" name="last_name" autocomplete="family-name"
                    placeholder="Familiya" :error="form.errors.last_name" />
            </div>

            <!-- Phone -->
            <Input v-model="form.phone" type="tel" label="Telefon raqam" name="phone" autocomplete="tel" inputmode="tel"
                placeholder="+998901234567" :leading-icon="PhoneIcon" :error="form.errors.phone" />

            <!-- Password -->
            <Input v-model="form.password" type="password" revealable label="Parol" name="password"
                autocomplete="new-password" placeholder="••••••••" :leading-icon="LockClosedIcon"
                :error="form.errors.password" />

            <Input v-model="form.password_confirmation" type="password" revealable label="Parolni tasdiqlang"
                name="password_confirmation" autocomplete="new-password" placeholder="••••••••"
                :leading-icon="LockClosedIcon" :error="form.errors.password_confirmation" />

            <Button type="submit" variant="primary" size="lg" block :loading="form.processing">
                Ro'yxatdan o'tish
            </Button>
        </form>

        <p class="mt-8 text-center text-sm text-fg-muted">
            Hisobingiz bormi?
            <Link href="/login" class="focus-ring rounded-sm font-medium text-brand hover:underline underline-offset-4">
                Kirish
            </Link>
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
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
