<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

defineProps({
    certificate: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>

    <Head :title="`Sertifikat - ${certificate.certificate_number}`" />

    <StudentLayout>
        <div class="max-w-4xl mx-auto py-8">
            <div class="flex items-center justify-between mb-6">
                <Link :href="route('student.certificates.index')" class="text-sm text-purple-600 hover:underline">
                &larr; Sertifikatlarga qaytish
                </Link>
                <a :href="route('student.certificates.download', certificate.id)"
                    class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Yuklab olish (PDF)
                </a>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-lg text-center">
                <!-- Certificate Preview (Simplified HTML version) -->
                <div class="border-8 border-double border-gray-200 p-12 rounded-xl bg-gray-50 relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-5 pointer-events-none">
                        <svg width="100%" height="100%">
                            <pattern id="pattern-circles" x="0" y="0" width="20" height="20"
                                patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1" class="text-gray-900" fill="currentColor" />
                            </pattern>
                            <rect width="100%" height="100%" fill="url(#pattern-circles)" />
                        </svg>
                    </div>

                    <div class="relative z-10">
                        <div
                            class="w-24 h-24 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-8 shadow-xl">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>

                        <h1 class="text-4xl font-serif font-bold text-gray-900 mb-4 tracking-wide">SERTIFIKAT</h1>
                        <p class="text-gray-500 uppercase tracking-widest mb-8">Muvaffaqiyatli tamomlaganligi uchun</p>

                        <h2 class="text-3xl font-bold text-purple-600 mb-6 italic">{{ certificate.user.full_name }}</h2>

                        <p class="text-gray-600 mb-2">Ushbu sertifikat tasdiqlaydiki, yuqoridagi shaxs</p>
                        <h3 class="text-xl font-bold text-gray-900 mb-8">{{ certificate.course.title }}</h3>
                        <p class="text-gray-600 mb-12">kursini muvaffaqiyatli tamomladi.</p>

                        <div class="flex justify-between items-end mt-16 pt-8 border-t border-gray-300">
                            <div class="text-left">
                                <p class="font-bold text-gray-900">{{ formatDate(certificate.issued_at) }}</p>
                                <p class="text-xs text-gray-500 uppercase">Berilgan sana</p>
                            </div>
                            <div class="text-center">
                                <p class="font-mono text-sm text-gray-600 mb-1">{{ certificate.certificate_number }}</p>
                                <p class="text-xs text-gray-500 uppercase">ID raqami</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">{{ certificate.course.teacher.full_name }}</p>
                                <p class="text-xs text-gray-500 uppercase">O'qituvchi imzosi</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
