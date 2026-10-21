<template>
    <GuestLayout>

        <Head title="Ro'yxatdan o'tish" />

        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-fg">Ro'yxatdan o'tish</h1>
            <p class="mt-1.5 text-sm text-fg-muted">Telefon raqamingizni yozing — tasdiqlash Telegram botimiz orqali, parol kerak emas</p>
        </div>

        <!-- registration only through the Telegram bot (owner's decision): students and parents -->
        <TelegramPhoneLogin mode="register" :initial-role="initialRole" />

        <p class="mt-8 text-center text-sm text-fg-muted">
            Hisobingiz bormi?
            <Link href="/login" class="focus-ring rounded-sm font-medium text-brand hover:underline underline-offset-4">
                Kirish
            </Link>
        </p>
    </GuestLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import TelegramPhoneLogin from '@/Components/Auth/TelegramPhoneLogin.vue';

// /register?role=guardian (the old parent sign-up link) preselects "Ota-onaman"
const initialRole = /[?&]role=guardian\b/.test(window.location.search) ? 'guardian' : 'student';
</script>
