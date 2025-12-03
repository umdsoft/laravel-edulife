<template>
    <GuestLayout>

        <Head title="Ro'yxatdan o'tish" />

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Ro'yxatdan o'tish</h2>
            <p class="text-gray-500">Yangi hisob yarating va o'qishni boshlang</p>
        </div>

        <form @submit.prevent="submit">
            <!-- Name Fields -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <Input v-model="form.first_name" type="text" label="Ism" placeholder="Ism"
                    :error="form.errors.first_name" />

                <Input v-model="form.last_name" type="text" label="Familiya" placeholder="Familiya"
                    :error="form.errors.last_name" />
            </div>

            <!-- Phone Input -->
            <div class="mb-4">
                <Input v-model="form.phone" type="text" label="Telefon raqam" placeholder="+998901234567"
                    :error="form.errors.phone" />
            </div>

            <!-- Password Fields -->
            <div class="mb-4">
                <Input v-model="form.password" type="password" label="Parol" placeholder="••••••••"
                    :error="form.errors.password" />
            </div>

            <div class="mb-6">
                <Input v-model="form.password_confirmation" type="password" label="Parolni tasdiqlang"
                    placeholder="••••••••" />
            </div>

            <!-- Submit Button -->
            <Button type="submit" variant="primary" :loading="form.processing" class="w-full mb-4">
                Ro'yxatdan o'tish
            </Button>

            <!-- Login Link -->
            <p class="text-center text-sm text-gray-600">
                Hisobingiz bormi?
                <Link href="/login" class="text-primary hover:text-primary/80 font-medium">
                Kirish
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
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
