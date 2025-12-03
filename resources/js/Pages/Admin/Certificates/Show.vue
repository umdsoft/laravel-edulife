<template>
    <AdminLayout>

        <Head title="Sertifikat Tafsilotlari" />

        <div class="mb-6">
            <Link href="/admin/certificates" class="text-gray-500 hover:text-gray-700 flex items-center gap-2 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Orqaga
            </Link>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">{{ certificate.code }}</h1>
                <div class="flex gap-2">
                    <a v-if="certificate.pdf_url" :href="`/admin/certificates/${certificate.id}/download`"
                        class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Yuklab olish
                    </a>
                    <button @click="regenerate"
                        class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Regenerate
                    </button>
                    <button @click="destroy"
                        class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-xl flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        O'chirish
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Info -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Talaba</h2>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ certificate.student.name }}</p>
                        <p class="text-sm text-gray-500">{{ certificate.student.email }}</p>
                        <p class="text-sm text-gray-500">{{ certificate.student.phone }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Kurs</h2>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ certificate.course.title }}</p>
                        <p class="text-sm text-gray-500">{{ certificate.course.direction }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Sertifikat</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Shablon</span>
                            <span class="text-sm text-gray-900">{{ certificate.template_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Berilgan sana</span>
                            <span class="text-sm text-gray-900">{{ certificate.issued_at }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Tekshirilgan</span>
                            <span class="text-sm text-gray-900">{{ certificate.verification_count }} marta</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-2">Verification URL</p>
                        <div class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg">
                            <input readonly :value="certificate.verification_url"
                                class="bg-transparent border-0 p-0 text-xs text-gray-600 w-full focus:ring-0" />
                            <button class="text-primary hover:text-primary/80" title="Copy">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Preview -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">PDF Preview</h2>
                    <div
                        class="relative w-full aspect-[1.414] bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <iframe v-if="certificate.pdf_url" :src="certificate.pdf_url" class="w-full h-full"></iframe>
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            PDF mavjud emas
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">QR Kod</h2>
                    <div class="flex justify-center">
                        <img v-if="certificate.qr_code_url" :src="certificate.qr_code_url" class="w-48 h-48" />
                        <div v-else
                            class="w-48 h-48 bg-gray-100 flex items-center justify-center text-gray-400 rounded-lg">
                            QR mavjud emas
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    certificate: Object,
});

const regenerate = () => {
    if (confirm('Sertifikatni qayta generatsiya qilmoqchimisiz?')) {
        router.post(`/admin/certificates/${props.certificate.id}/regenerate`);
    }
};

const destroy = () => {
    if (confirm('Sertifikatni o\'chirmoqchimisiz?')) {
        router.delete(`/admin/certificates/${props.certificate.id}`);
    }
};
</script>
