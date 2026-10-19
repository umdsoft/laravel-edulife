<template>
    <AdminLayout>

        <Head title="Foydalanuvchini tahrirlash" />

        <PageHeader title="Foydalanuvchini tahrirlash" :description="`${user.first_name} ${user.last_name}`"
            :breadcrumbs="[
                { label: 'Foydalanuvchilar', href: route('admin.users.index') },
                { label: `${user.first_name} ${user.last_name}`, href: route('admin.users.show', user.id) },
                { label: 'Tahrirlash' },
            ]" />

        <form class="max-w-2xl space-y-6" novalidate @submit.prevent="submit">
            <Card title="Shaxsiy ma'lumotlar">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Input v-model="form.first_name" label="Ism" autocomplete="given-name" :error="form.errors.first_name"
                        required />
                    <Input v-model="form.last_name" label="Familiya" autocomplete="family-name"
                        :error="form.errors.last_name" required />
                    <Input v-model="form.phone" type="tel" inputmode="tel" label="Telefon raqam" autocomplete="tel"
                        :error="form.errors.phone" required />
                    <Input v-model="form.email" type="email" label="Email" autocomplete="email" :error="form.errors.email"
                        optional />
                </div>
            </Card>

            <Card title="Rol va holat">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Select v-model="form.role" label="Rol" :options="roleOptions" :error="form.errors.role" />
                    <Select v-model="form.status" label="Holat" :options="statusOptions" :error="form.errors.status"
                        hint="Bloklangan foydalanuvchi tizimga kira olmaydi" />
                </div>
            </Card>

            <Card title="Parolni almashtirish" description="Eski parolni saqlash uchun bo'sh qoldiring">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Input v-model="form.password" type="password" label="Yangi parol" autocomplete="new-password"
                        :error="form.errors.password" optional revealable />
                    <Input v-model="form.password_confirmation" type="password" label="Parolni tasdiqlang"
                        autocomplete="new-password" :error="form.errors.password_confirmation" revealable />
                </div>
            </Card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <Button variant="secondary" :href="route('admin.users.index')">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Yangilash</Button>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button, Card, Input, PageHeader, Select } from '@/Components/UI';

const props = defineProps({
    user: Object,
});

const roleOptions = [
    { value: 'student', label: 'O\'quvchi' },
    { value: 'teacher', label: 'O\'qituvchi' },
    { value: 'admin', label: 'Admin' },
    { value: 'super_admin', label: 'Super admin' },
];

const statusOptions = [
    { value: 'active', label: 'Faol' },
    { value: 'inactive', label: 'Nofaol' },
    { value: 'blocked', label: 'Bloklangan' },
];

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
