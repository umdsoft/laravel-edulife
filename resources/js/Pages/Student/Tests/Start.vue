<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    test: Object,
    course: Object,
    previousAttempts: Array,
    canStart: Boolean,
    prerequisitesMet: Boolean,
    bestScore: Number,
    isPassed: Boolean,
    remainingAttempts: Number
});

const form = useForm({
    confirm: false
});

const startTest = () => {
    form.post(route('student.tests.begin', props.test.id));
};
</script>

<template>
    <StudentLayout>

        <Head :title="test.title" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <Link :href="route('student.tests.index', course.id)"
                    class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
                ← Testlar ro'yxatiga qaytish
                </Link>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ test.title }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">
                    {{ course.title }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Info Card -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            📊 Test ma'lumotlari
                        </h2>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Savollar soni</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ test.questions_count }}
                                    ta</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Vaqt chegarasi</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ test.time_limit }}
                                    daqiqa</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">O'tish balli</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ test.pass_rate }}%</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Maksimal urinishlar</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ test.max_attempts }} ta
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Savol turlari:</h3>
                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li>Bitta to'g'ri javob</li>
                                <li>Bir nechta to'g'ri javob</li>
                                <li>Bo'sh joyni to'ldirish</li>
                                <li>Moslashtirish</li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
                        <h2
                            class="text-lg font-semibold text-yellow-800 dark:text-yellow-400 mb-4 flex items-center gap-2">
                            ⚠️ Muhim qoidalar
                        </h2>
                        <ul class="space-y-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <li class="flex items-start gap-2">
                                <span>•</span>
                                Test boshlangach, vaqt to'xtamaydi va ortga qaytarib bo'lmaydi.
                            </li>
                            <li class="flex items-start gap-2">
                                <span>•</span>
                                <strong>Anti-cheat tizimi faol:</strong> Boshqa oynaga o'tish yoki testdan chiqish
                                taqiqlanadi. Qoidabuzarliklar qayd etiladi.
                            </li>
                            <li class="flex items-start gap-2">
                                <span>•</span>
                                Vaqt tugashi bilan test avtomatik ravishda topshiriladi.
                            </li>
                            <li class="flex items-start gap-2">
                                <span>•</span>
                                Natijalar darhol e'lon qilinadi va javoblarni ko'rib chiqish imkoniyati beriladi (test
                                sozlamalariga qarab).
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            📈 Sizning holatingiz
                        </h2>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Eng yaxshi natija:</span>
                                <span class="font-bold"
                                    :class="isPassed ? 'text-green-600' : 'text-gray-900 dark:text-white'">
                                    {{ Math.round(bestScore) }}%
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Qolgan urinishlar:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ remainingAttempts }}</span>
                            </div>
                        </div>

                        <div v-if="!prerequisitesMet" class="p-3 bg-red-50 text-red-700 rounded text-sm mb-4">
                            🔒 Ushbu testni boshlash uchun avvalgi darslarni yoki modullarni tugatishingiz kerak.
                        </div>

                        <div v-else-if="!canStart" class="p-3 bg-orange-50 text-orange-700 rounded text-sm mb-4">
                            🔒 Urinishlar soni tugadi.
                        </div>

                        <div v-else>
                            <label class="flex items-start gap-2 mb-4 cursor-pointer">
                                <input type="checkbox" v-model="form.confirm"
                                    class="mt-1 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    Men qoidalarni o'qidim va testni halol topshirishga va'da beraman.
                                </span>
                            </label>

                            <button @click="startTest" :disabled="!form.confirm || form.processing"
                                class="w-full py-3 px-4 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-lg shadow-purple-200 dark:shadow-none">
                                🚀 Testni Boshlash
                            </button>
                        </div>
                    </div>

                    <!-- History Preview -->
                    <div v-if="previousAttempts.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-3">Oldingi urinishlar</h3>
                        <div class="space-y-3">
                            <div v-for="attempt in previousAttempts" :key="attempt.id"
                                class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">{{ new Date(attempt.created_at).toLocaleDateString()
                                    }}</span>
                                <span class="font-medium"
                                    :class="attempt.is_passed ? 'text-green-600' : 'text-red-600'">
                                    {{ Math.round(attempt.score) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
