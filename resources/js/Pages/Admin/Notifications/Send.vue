<template>
    <AdminLayout>

        <Head title="Xabar Yuborish" />

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Xabar Yuborish</h1>
                <p class="text-gray-500">Foydalanuvchilarga xabar yuborish</p>
            </div>
            <Link href="/admin/notifications/templates"
                class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl transition-colors">
            Shablonlar
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Send Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Yangi xabar</h2>
                        <select v-model="selectedTemplate" @change="applyTemplate"
                            class="text-sm border-gray-200 rounded-lg focus:ring-primary/20 focus:border-primary">
                            <option :value="null">Shablon tanlash...</option>
                            <option v-for="t in templates" :key="t.id" :value="t">{{ t.name }}</option>
                        </select>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <Input v-model="form.title" label="Sarlavha" :error="form.errors.title" required
                            placeholder="Xabar sarlavhasi..." />

                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wide mb-2">
                                Xabar matni
                            </label>
                            <textarea v-model="form.message" rows="6"
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
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    templates: Array,
});

const selectedTemplate = ref(null);

const form = useForm({
    title: '',
    message: '',
    type: 'all',
    user_ids: [],
});

const applyTemplate = () => {
    if (selectedTemplate.value) {
        form.title = selectedTemplate.value.title;
        form.message = selectedTemplate.value.message;
        form.type = selectedTemplate.value.type;
    }
};

const submit = () => {
    form.post('/admin/notifications/send', {
        onSuccess: () => {
            form.reset();
            selectedTemplate.value = null;
        },
    });
};
</script>
