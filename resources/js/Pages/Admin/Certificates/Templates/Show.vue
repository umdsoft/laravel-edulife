<template>
    <AdminLayout>

        <Head title="Shablon Tafsilotlari" />

        <div class="mb-6">
            <Link href="/admin/certificate-templates"
                class="text-gray-500 hover:text-gray-700 flex items-center gap-2 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Orqaga
            </Link>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ template.name }}</h1>
                <div class="flex gap-2">
                    <Link :href="`/admin/certificate-templates/${template.id}/edit`"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Tahrirlash
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Info -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ma'lumotlar</h2>

                    <div class="space-y-4">
                        <div>
                            <span class="text-sm text-gray-500 block">Holati</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                {{ template.is_active ? 'Faol' : 'Nofaol' }}
                            </span>
                            <span v-if="template.is_default"
                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Default
                            </span>
                        </div>

                        <div>
                            <span class="text-sm text-gray-500 block">Yo'nalish</span>
                            <span class="text-gray-900">{{ template.direction?.name || 'Barcha yo\'nalishlar' }}</span>
                        </div>

                        <div>
                            <span class="text-sm text-gray-500 block">Kurs</span>
                            <span class="text-gray-900">{{ template.course?.title || 'Barcha kurslar' }}</span>
                        </div>

                        <div>
                            <span class="text-sm text-gray-500 block">Berilgan sertifikatlar</span>
                            <span class="text-gray-900 font-medium">{{ template.usage_count }} ta</span>
                        </div>

                        <div>
                            <span class="text-sm text-gray-500 block">Yaratilgan sana</span>
                            <span class="text-gray-900">{{ new Date(template.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">So'nggi sertifikatlar</h2>
                    <div class="space-y-3">
                        <div v-for="cert in recentCertificates" :key="cert.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ cert.user?.full_name ||
                                    cert.user?.first_name }}</p>
                                <p class="text-xs text-gray-500">{{ cert.certificate_code }}</p>
                            </div>
                            <Link :href="`/admin/certificates/${cert.id}`" class="text-primary hover:text-primary/80">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                            </Link>
                        </div>
                        <p v-if="recentCertificates.length === 0" class="text-sm text-gray-500 text-center py-2">
                            Hozircha sertifikatlar yo'q
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Preview -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ko'rinishi</h2>

                    <div
                        class="relative w-full aspect-[1.414] bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <img v-if="template.thumbnail_path" :src="`/storage/${template.thumbnail_path}`"
                            class="w-full h-full object-contain" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            Preview mavjud emas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    template: Object,
    recentCertificates: Array,
});
</script>
