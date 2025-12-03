<template>
    <AdminLayout>

        <Head title="Xabarnomalar" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Xabarnomalar</h1>
            <p class="text-gray-500">Foydalanuvchilarga xabar yuborish</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Send Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Yangi xabar yuborish</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <Input v-model="form.title" label="Sarlavha" :error="form.errors.title" required
                            placeholder="Xabar sarlavhasi..." />

                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                                Xabar matni
                            </label>
                            <textarea v-model="form.message" rows="4"
                                class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20"
                                placeholder="Xabar matnini kiriting..." required></textarea>
                            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                                Kimga
                            </label>
                            <select v-model="form.type"
                                class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary/20">
                                <option value="all">Barcha foydalanuvchilar</option>
                                <option value="teachers">Faqat o'qituvchilar</option>
                                <option value="students">Faqat talabalar</option>
                                <!-- <option value="specific">Tanlangan foydalanuvchilar</option> -->
                            </select>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button variant="primary" :loading="form.processing">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Yuborish
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Notifications (Mock) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">So'nggi xabarlar</h2>

                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="flex items-start justify-between mb-2">
                                <span
                                    class="text-xs font-medium text-primary bg-primary/5 px-2 py-1 rounded">Barchaga</span>
                                <span class="text-xs text-gray-500">Bugun, 10:00</span>
                            </div>
                            <h3 class="font-medium text-gray-900 mb-1">Tizim yangilanishi</h3>
                            <p class="text-sm text-gray-600">Hurmatli foydalanuvchilar, tizimda texnik ishlar olib
                                borilmoqda.</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="flex items-start justify-between mb-2">
                                <span
                                    class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">O'qituvchilar</span>
                                <span class="text-xs text-gray-500">Kecha, 15:30</span>
                            </div>
                            <h3 class="font-medium text-gray-900 mb-1">Yangi funksiyalar</h3>
                            <p class="text-sm text-gray-600">O'qituvchilar uchun yangi statistika bo'limi qo'shildi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const form = useForm({
    title: '',
    message: '',
    type: 'all',
    user_ids: [],
});

const submit = () => {
    form.post('/admin/notifications/send', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
