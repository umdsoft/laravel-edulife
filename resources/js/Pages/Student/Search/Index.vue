<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps({
    query: String,
    courses: Object,
    teachers: Array,
    directions: Array,
});

const searchQuery = ref(props.query);

const search = debounce(() => {
    router.get(route('student.search.index'), { q: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(searchQuery, search);
</script>

<template>

    <Head title="Qidiruv" />

    <StudentLayout>
        <!-- Search Header -->
        <div class="mb-8">
            <div class="relative max-w-2xl mx-auto">
                <input type="text" v-model="searchQuery"
                    placeholder="Kurslar, o'qituvchilar yoki yo'nalishlarni izlash..."
                    class="w-full pl-12 pr-4 py-4 rounded-2xl border-gray-200 shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent text-lg"
                    autofocus>
                <svg class="w-6 h-6 text-gray-400 absolute left-4 top-4.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div v-if="query">
            <h1 class="text-xl font-bold text-gray-900 mb-6">
                "{{ query }}" bo'yicha qidiruv natijalari
            </h1>

            <!-- Teachers -->
            <section v-if="teachers.length > 0" class="mb-12">
                <h2 class="text-lg font-bold text-gray-900 mb-4">O'qituvchilar</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="teacher in teachers" :key="teacher.id"
                        class="bg-white p-4 rounded-xl border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
                        <img :src="teacher.avatar_url || `https://ui-avatars.com/api/?name=${teacher.first_name}+${teacher.last_name}`"
                            class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ teacher.full_name }}</h4>
                            <p class="text-sm text-gray-500">{{ teacher.courses_count }} ta kurs</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Courses -->
            <section v-if="courses.data.length > 0">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Kurslar</h2>
                <div class="space-y-4">
                    <div v-for="course in courses.data" :key="course.id" class="h-40">
                        <CourseCardHorizontal :course="course" />
                    </div>
                </div>
                <Pagination :data="courses" class="mt-8" />
            </section>

            <!-- No Results -->
            <div v-else-if="teachers.length === 0"
                class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">🔍</span>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Hech narsa topilmadi</h3>
                <p class="text-gray-500 mt-2">Boshqa so'z bilan izlab ko'ring</p>
            </div>
        </div>

        <!-- Default View (Categories) -->
        <div v-else>
            <h2 class="text-xl font-bold text-gray-900 mb-6">Ommabop yo'nalishlar</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <Link v-for="direction in directions" :key="direction.id"
                    :href="route('student.courses.category', direction.id)"
                    class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-md hover:border-purple-100 transition-all text-center group">
                <div
                    class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-100 transition-colors">
                    <img v-if="direction.icon_url" :src="direction.icon_url" class="w-6 h-6 object-contain">
                    <span v-else class="text-2xl">📚</span>
                </div>
                <h3 class="font-bold text-gray-900 group-hover:text-purple-600 transition-colors">{{ direction.name }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">{{ direction.courses_count }} kurslar</p>
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
