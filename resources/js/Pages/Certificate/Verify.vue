<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">

        <Head title="Sertifikatni Tekshirish" />

        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary">EDULIFE</h1>
                <p class="text-gray-500">Sertifikatni tekshirish tizimi</p>
            </div>

            <!-- Result Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <!-- Status Header -->
                <div class="px-6 py-8 text-center" :class="found ? 'bg-green-50' : 'bg-red-50'">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                        :class="found ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'">
                        <svg v-if="found" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold" :class="found ? 'text-green-900' : 'text-red-900'">
                        {{ found ? 'SERTIFIKAT TASDIQLANDI' : 'SERTIFIKAT TOPILMADI' }}
                    </h2>
                </div>

                <!-- Certificate Details -->
                <div v-if="found" class="p-6 space-y-4">
                    <div class="space-y-3">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-sm">Sertifikat raqami</span>
                            <span class="font-mono font-medium text-gray-900">{{ certificate.code }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-sm">Talaba</span>
                            <span class="font-medium text-gray-900">{{ certificate.student_name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-sm">Kurs</span>
                            <span class="font-medium text-gray-900">{{ certificate.course_title }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-sm">Yo'nalish</span>
                            <span class="font-medium text-gray-900">{{ certificate.course_direction }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-sm">Berilgan sana</span>
                            <span class="font-medium text-gray-900">{{ certificate.issued_at }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Tekshirilgan</span>
                            <span class="font-medium text-gray-900">{{ certificate.verification_count }} marta</span>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a v-if="certificate.pdf_url" :href="certificate.pdf_url" target="_blank"
                            class="block w-full bg-primary hover:bg-primary/90 text-white text-center py-3 rounded-xl font-medium transition-colors">
                            PDF Yuklab olish
                        </a>
                    </div>

                    <div class="text-center pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            Bu sertifikat EDULIFE platformasi tomonidan berilgan va uning haqiqiyligi tasdiqlangan.
                        </p>
                    </div>
                </div>

                <!-- Not Found Message -->
                <div v-else class="p-6 text-center space-y-4">
                    <p class="text-gray-600">
                        Kiritilgan kod bo'yicha sertifikat topilmadi. Iltimos, kodni tekshirib qaytadan urinib ko'ring.
                    </p>
                    <p class="text-sm text-gray-500">
                        Agar muammo davom etsa, <a href="mailto:support@edulife.uz"
                            class="text-primary hover:underline">support@edulife.uz</a> ga murojaat qiling.
                    </p>
                    <div class="pt-4">
                        <a href="/" class="text-primary font-medium hover:underline">Bosh sahifaga qaytish</a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-400">
                &copy; {{ new Date().getFullYear() }} EDULIFE. All rights reserved.
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    found: Boolean,
    certificate: Object,
});
</script>
