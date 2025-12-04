<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseFilters from '@/Components/Student/CourseFilters.vue';
import Pagination from '@/Components/UI/Pagination.vue';
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
    description: 'EDULIFE platformasidagi barcha kurslarni ko\'ring. Dasturlash, dizayn, marketing va boshqa yo\'nalishlarda professional ta\'lim oling.',
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
        <script type="application/ld+json">{{ JSON.stringify(breadcrumbs) }}</script>
    </Head>

    <StudentLayout>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters (Sidebar) -->
            <aside class="w-full lg:w-64 shrink-0">
                <CourseFilters :filters="filters" :directions="directions" />
            </aside>

            <!-- Main Content -->
            <div class="flex-1">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Barcha kurslar</h1>
                    <p class="text-gray-500 mt-1">O'zingizga mos kursni toping va o'rganishni boshlang</p>
                </div>

                <!-- Course Grid -->
                <div v-if="courses.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    <CourseCard v-for="course in courses.data" :key="course.id" :course="course" />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl">🔍</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Kurslar topilmadi</h3>
                    <p class="text-gray-500 mt-2">Filtrlarni o'zgartirib ko'ring yoki boshqa so'z bilan izlang</p>
                </div>

                <!-- Pagination -->
                <Pagination :data="courses" />
            </div>
        </div>
    </StudentLayout>
</template>
