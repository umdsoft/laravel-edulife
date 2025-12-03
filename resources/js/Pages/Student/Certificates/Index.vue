<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CertificateCard from '@/Components/Student/CertificateCard.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    certificates: Object,
});
</script>

<template>

    <Head title="Mening sertifikatlarim" />

    <StudentLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Mening sertifikatlarim</h1>
            <p class="text-gray-500 mt-1">Tugatilgan kurslar uchun berilgan sertifikatlar</p>
        </div>

        <div v-if="certificates.data.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <CertificateCard v-for="certificate in certificates.data" :key="certificate.id"
                :certificate="certificate" />
        </div>

        <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Sertifikatlar yo'q</h3>
            <p class="text-gray-500 mt-2">Siz hali birorta kursni tugatmagansiz va sertifikat olmagansiz.</p>
        </div>

        <Pagination :data="certificates" class="mt-8" />
    </StudentLayout>
</template>
