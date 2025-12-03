<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    wishlistItems: Object,
});

const removeFromWishlist = (course) => {
    if (confirm('Rostdan ham bu kursni istaklar ro\'yxatidan o\'chirmoqchimisiz?')) {
        router.post(route('student.wishlist.toggle', course.id));
    }
};
</script>

<template>

    <Head title="Istaklar ro'yxati" />

    <StudentLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Istaklar ro'yxati</h1>
            <p class="text-gray-500 mt-1">Sizga yoqqan va keyinroq o'qimoqchi bo'lgan kurslar</p>
        </div>

        <!-- Wishlist Items -->
        <div v-if="wishlistItems.data.length > 0" class="space-y-4">
            <div v-for="item in wishlistItems.data" :key="item.id" class="relative group">
                <div class="h-40">
                    <CourseCardHorizontal :course="item.course" />
                </div>

                <!-- Remove Button -->
                <button @click="removeFromWishlist(item.course)"
                    class="absolute top-4 right-4 p-2 bg-white rounded-lg shadow-sm text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100"
                    title="O'chirish">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12 bg-white rounded-2xl border border-gray-100">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl">❤️</span>
            </div>
            <h3 class="text-lg font-bold text-gray-900">Istaklar ro'yxati bo'sh</h3>
            <p class="text-gray-500 mt-2 mb-6">Sizga yoqqan kurslarni shu yerda saqlashingiz mumkin</p>
            <Link :href="route('student.courses.index')"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-purple-600 hover:bg-purple-700">
            Kurslarni ko'rish
            </Link>
        </div>

        <!-- Pagination -->
        <Pagination :data="wishlistItems" class="mt-8" />
    </StudentLayout>
</template>
