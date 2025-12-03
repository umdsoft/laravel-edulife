<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import ProgressBar from '@/Components/Student/ProgressBar.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';

defineProps({
    course: Object,
    enrollment: Object,
    lessonProgresses: Object,
    moduleProgress: Object,
    nextLesson: Object,
    announcementsCount: Number,
    qnaCount: Number,
});
</script>

<template>

    <Head :title="course.title" />

    <StudentLayout>
        <div class="flex h-[calc(100vh-64px)] -m-6">
            <!-- Main Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Course Header -->
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm mb-8">
                    <div class="flex items-start justify-between gap-8">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-4">
                                <span
                                    class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full uppercase tracking-wide">
                                    {{ course.category?.name || 'Kurs' }}
                                </span>
                                <div class="flex items-center gap-1 text-yellow-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="text-gray-700 text-sm font-medium">{{ course.avg_rating }}</span>
                                </div>
                            </div>

                            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ course.title }}</h1>

                            <div class="flex items-center gap-4 mb-8">
                                <div class="flex items-center gap-2">
                                    <img :src="course.teacher.avatar_url || `https://ui-avatars.com/api/?name=${course.teacher.first_name}+${course.teacher.last_name}`"
                                        class="w-8 h-8 rounded-full border border-gray-200">
                                    <span class="text-gray-700 font-medium">{{ course.teacher.full_name }}</span>
                                </div>
                                <span class="text-gray-300">|</span>
                                <span class="text-gray-500">{{ course.lessons_count }} ta dars</span>
                                <span class="text-gray-300">|</span>
                                <span class="text-gray-500">{{ course.total_duration_human }}</span>
                            </div>

                            <div class="flex gap-4">
                                <Link v-if="nextLesson"
                                    :href="route('student.learn.lesson', { course: course.slug, lesson: nextLesson.id })"
                                    class="px-8 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors shadow-lg shadow-purple-200 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ enrollment.progress > 0 ? 'Davom ettirish' : 'Boshlash' }}
                                </Link>

                                <Link :href="route('student.reviews.create', course.id)"
                                    class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-colors">
                                Sharh qoldirish
                                </Link>
                            </div>
                        </div>

                        <!-- Progress Card -->
                        <div class="w-72 bg-gray-50 rounded-xl p-6 border border-gray-100">
                            <div class="text-center mb-4">
                                <div class="text-4xl font-bold text-purple-600 mb-1">{{ enrollment.progress }}%</div>
                                <div class="text-sm text-gray-500">Umumiy progress</div>
                            </div>
                            <ProgressBar :percentage="enrollment.progress" height="h-3" />
                            <div class="mt-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Tugatilgan darslar:</span>
                                    <span class="font-medium text-gray-900">{{ enrollment.completed_lessons }}/{{
                                        enrollment.total_lessons }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Sertifikat:</span>
                                    <span v-if="enrollment.is_completed"
                                        class="text-green-600 font-medium">Mavjud</span>
                                    <span v-else class="text-gray-400">Qulflangan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Link :href="route('student.learn.notes.index', course.id)"
                        class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-md transition-shadow group">
                    <div
                        class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1">Mening qaydlarim</h3>
                    <p class="text-sm text-gray-500">Dars davomida olingan barcha qaydlar</p>
                    </Link>

                    <Link :href="route('student.learn.qna.index', course.id)"
                        class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-md transition-shadow group">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex justify-between items-start">
                        <h3 class="font-bold text-gray-900 mb-1">Savol-javoblar</h3>
                        <span v-if="qnaCount > 0"
                            class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ qnaCount
                            }}</span>
                    </div>
                    <p class="text-sm text-gray-500">O'qituvchi va talabalar bilan muloqot</p>
                    </Link>

                    <Link :href="route('student.learn.announcements.index', course.id)"
                        class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-md transition-shadow group">
                    <div
                        class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <div class="flex justify-between items-start">
                        <h3 class="font-bold text-gray-900 mb-1">E'lonlar</h3>
                        <span v-if="announcementsCount > 0"
                            class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{
                            announcementsCount }}</span>
                    </div>
                    <p class="text-sm text-gray-500">Kurs yangiliklari va e'lonlar</p>
                    </Link>
                </div>

                <!-- Course Content (Mobile only here, Sidebar on Desktop) -->
                <div class="lg:hidden bg-white rounded-2xl border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-bold text-gray-900">Kurs dasturi</h3>
                    </div>
                    <LessonSidebar :course="course" :progress="lessonProgresses" :module-progress="moduleProgress"
                        class="w-full !block !h-auto !border-0" />
                </div>
            </div>

            <!-- Sidebar (Desktop) -->
            <LessonSidebar :course="course" :progress="lessonProgresses" :module-progress="moduleProgress" />
        </div>
    </StudentLayout>
</template>
