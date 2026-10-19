<script setup>
import { Head } from '@inertiajs/vue3';
import { DocumentCheckIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, EmptyState, Pagination, Button } from '@/Components/UI';
import CertificateCard from '@/Components/Student/CertificateCard.vue';

defineProps({
    certificates: Object,
});
</script>

<template>

    <Head title="Mening sertifikatlarim" />

    <StudentLayout title="Sertifikatlar">
        <PageHeader title="Mening sertifikatlarim" description="Tugatilgan kurslar uchun berilgan sertifikatlar." />

        <div class="space-y-6">
            <div v-if="certificates.data.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <CertificateCard v-for="certificate in certificates.data" :key="certificate.id" :certificate="certificate" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="DocumentCheckIcon" title="Sertifikatlar yo'q"
                description="Kursni to'liq tugatganingizda sertifikat avtomatik beriladi.">
                <Button variant="secondary" :href="route('student.my-courses.index')">Kurslarimga o'tish</Button>
            </EmptyState>

            <Pagination :data="certificates" />
        </div>
    </StudentLayout>
</template>
