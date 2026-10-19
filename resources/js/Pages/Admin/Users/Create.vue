<template>
    <AdminLayout>

        <Head title="Yangi foydalanuvchi" />

        <PageHeader title="Yangi foydalanuvchi" description="Foydalanuvchi ma'lumotlarini kiriting"
            :breadcrumbs="[{ label: 'Foydalanuvchilar', href: route('admin.users.index') }, { label: 'Yangi foydalanuvchi' }]" />

        <form class="max-w-2xl space-y-6" novalidate @submit.prevent="submit">
            <Card title="Shaxsiy ma'lumotlar">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Input v-model="form.first_name" label="Ism" placeholder="Ism" autocomplete="given-name"
                        :error="form.errors.first_name" required autofocus />
                    <Input v-model="form.last_name" label="Familiya" placeholder="Familiya" autocomplete="family-name"
                        :error="form.errors.last_name" required />
                    <Input v-model="form.phone" type="tel" inputmode="tel" label="Telefon raqam"
                        placeholder="+998901234567" autocomplete="tel" :error="form.errors.phone" required />
                    <Input v-model="form.email" type="email" label="Email" placeholder="email@example.com"
                        autocomplete="email" :error="form.errors.email" optional />
                </div>
            </Card>

            <Card title="Kirish va rol" description="Foydalanuvchi shu parol bilan tizimga kiradi">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Input v-model="form.password" type="password" label="Parol" autocomplete="new-password"
                        :error="form.errors.password" required revealable />
                    <Input v-model="form.password_confirmation" type="password" label="Parolni tasdiqlang"
                        autocomplete="new-password" :error="form.errors.password_confirmation" revealable />
                    <Select v-model="form.role" label="Rol" :options="roleOptions" :error="form.errors.role"
                        class="sm:col-span-2" />
                </div>
            </Card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <Button variant="secondary" :href="route('admin.users.index')">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Saqlash</Button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button, Card, Input, PageHeader, Select } from '@/Components/UI';

const roleOptions = [
    { value: 'student', label: 'O\'quvchi' },
    { value: 'teacher', label: 'O\'qituvchi' },
    { value: 'admin', label: 'Admin' },
    { value: 'super_admin', label: 'Super admin' },
];

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'student',
});

const submit = () => {
    form.post(route('admin.users.store'));
};
</script>
