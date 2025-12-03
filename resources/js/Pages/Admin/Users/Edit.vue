<template>
    <AdminLayout>

        <Head title="Foydalanuvchini tahrirlash" />

        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('admin.users.index')"
                class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
            </Link>

            <h1 class="text-2xl font-bold text-gray-900">Foydalanuvchini tahrirlash</h1>
            <p class="text-gray-500">{{ user.first_name }} {{ user.last_name }}</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm p-6">
            <div class="space-y-6">
                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input v-model="form.first_name" label="Ism" :error="form.errors.first_name" required />

                    <Input v-model="form.last_name" label="Familiya" :error="form.errors.last_name" required />
                </div>

                <!-- Contact Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input v-model="form.phone" label="Telefon raqam" :error="form.errors.phone" required />

                    <Input v-model="form.email" type="email" label="Email (ixtiyoriy)" :error="form.errors.email" />
                </div>

                <!-- Password Fields (Optional) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Input v-model="form.password" type="password" label="Yangi parol (ixtiyoriy)"
                        placeholder="Eski parolni saqlash uchun bo'sh qoldiring" :error="form.errors.password" />

                    <Input v-model="form.password_confirmation" type="password" label="Parolni tasdiqlang"
                        placeholder="••••••••" />
                </div>

                <!-- Role & Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Rol
                        </label>
                        <select v-model="form.role"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3.5 text-gray-900 focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all duration-200">
                            <option value="student">Student</option>
                            <option value="teacher">O'qituvchi</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                            Status
                        </label>
                        <select v-model="form.status"
                            class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3.5 text-gray-900 focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all duration-200">
                            <option value="active">Faol</option>
                            <option value="inactive">Nofaol</option>
                            <option value="blocked">Bloklangan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
                <Link :href="route('admin.users.index')"
                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl font-medium transition-all duration-200 bg-gray-100 text-gray-900 hover:bg-gray-200">
                Bekor qilish
                </Link>
                <Button type="submit" variant="primary" :loading="form.processing">
                    Yangilash
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    phone: props.user.phone,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.user.role,
    status: props.user.status,
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};
</script>
