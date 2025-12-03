<template>
    <GuestLayout>

        <Head title="Kirish" />

        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Kirish</h2>
            <p class="text-gray-500">Hisobingizga kiring va o'qishni davom ettiring</p>
        </div>

        <form @submit.prevent="submit">
            <!-- Phone Input -->
            <div class="mb-4">
                <Input v-model="form.phone" type="text" label="Telefon raqam" placeholder="+998901234567"
                    :error="form.errors.phone" />
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <div class="relative">
                    <Input v-model="form.password" :type="showPassword ? 'text' : 'password'" label="Parol"
                        placeholder="••••••••" :error="form.errors.password" />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-4 top-11 text-gray-400 hover:text-gray-600">
                        <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="mb-6 flex items-center">
                <input v-model="form.remember" type="checkbox" id="remember"
                    class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2" />
                <label for="remember" class="ml-2 text-sm text-gray-600">
                    Meni eslab qol
                </label>
            </div>

            <!-- Submit Button -->
            <Button type="submit" variant="primary" :loading="form.processing" class="w-full mb-4">
                Kirish
            </Button>

            <!-- Register Link -->
            <p class="text-center text-sm text-gray-600">
                Hisobingiz yo'qmi?
                <Link href="/register" class="text-primary hover:text-primary/80 font-medium">
                Ro'yxatdan o'tish
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const showPassword = ref(false);

const form = useForm({
    phone: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
