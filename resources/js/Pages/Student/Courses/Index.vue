<script setup>
import { Head } from '@inertiajs/vue3';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, EmptyState, Pagination } from '@/Components/UI';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseFilters from '@/Components/Student/CourseFilters.vue';
import { useSeo } from '@/Composables/useSeo.js';
import { breadcrumbSchema } from '@/Utils/schema.js';

const props = defineProps({
    courses: Object,
    directions: Array,
    filters: Object,
});

// SEO configuration
const { seoMeta } = useSeo({
    title: 'Barcha Kurslar',
    description: 'EDULIFE platformasidagi matematika va fizika kurslarini ko\'ring: video darslar, testlar va amaliy masalalar.',
});

// Breadcrumb schema
const breadcrumbs = breadcrumbSchema([
    { name: 'Bosh sahifa', url: 'https://edulife.uz' },
    { name: 'Kurslar' },
]);
</script>

<template>
    <!-- SEO Meta Tags -->

    <Head>
        <title>{{ seoMeta.title }}</title>
        <meta name="description" :content="seoMeta.description" />
        <link rel="canonical" :href="seoMeta.canonical" />

        <!-- Open Graph -->
        <meta property="og:title" :content="seoMeta.ogTitle" />
        <meta property="og:description" :content="seoMeta.ogDescription" />
        <meta property="og:url" :content="seoMeta.ogUrl" />
        <meta property="og:type" content="website" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoMeta.twitterTitle" />
        <meta name="twitter:description" :content="seoMeta.twitterDescription" />

        <!-- Breadcrumb Schema -->
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(breadcrumbs) }}</component>
    </Head>

    <StudentLayout title="Kurslar">
        <PageHeader title="Barcha kurslar" description="O'zingizga mos kursni toping va o'rganishni boshlang.">
            <template v-if="courses?.total" #meta>
                <span class="text-13 text-fg-subtle tabular-nums">{{ courses.total }} ta kurs</span>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <aside class="w-full shrink-0 lg:w-64">
                <CourseFilters :filters="filters" :directions="directions" />
            </aside>

            <div class="min-w-0 flex-1 space-y-6">
                <div v-if="courses.data.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <CourseCard v-for="course in courses.data" :key="course.id" :course="course" />
                </div>

                <EmptyState v-else bordered size="lg" :icon="MagnifyingGlassIcon" title="Kurslar topilmadi"
                    description="Filtrlarni o'zgartirib ko'ring yoki boshqa so'z bilan izlang." />

                <Pagination :data="courses" />
            </div>
        </div>
    </StudentLayout>
</template>
