<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';

defineProps({
    activeBattle: Object,
});

const isSearching = ref(false);

const findOpponent = () => {
    isSearching.value = true;
    router.post(route('student.battles.find'), {}, {
        onFinish: () => isSearching.value = false,
    });
};
</script>

<template>

    <Head title="Janglar maydoni" />

    <StudentLayout>
        <div class="max-w-4xl mx-auto py-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Janglar maydoni</h1>
            <p class="text-xl text-gray-600 mb-12">Boshqa talabalar bilan bilimingizni sinang va XP yig'ing!</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <!-- 1v1 Battle -->
                <div
                    class="bg-white p-8 rounded-3xl border-2 border-purple-100 hover:border-purple-500 transition-all shadow-lg hover:shadow-xl group relative overflow-hidden">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    </div>

                    <div class="relative z-10">
                        <div
                            class="w-20 h-20 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">1 vs 1 Jang</h2>
                        <p class="text-gray-500 mb-8">Tasodifiy raqib bilan 5 ta savol bo'yicha bellashuv.</p>

                        <button @click="findOpponent" :disabled="isSearching"
                            class="w-full py-4 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors shadow-lg shadow-purple-200 disabled:opacity-75 flex items-center justify-center gap-2">
                            <svg v-if="isSearching" class="animate-spin h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span v-if="isSearching">Raqib qidirilmoqda...</span>
                            <span v-else>Jangni boshlash</span>
                        </button>
                    </div>
                </div>

                <!-- Tournaments -->
                <div
                    class="bg-white p-8 rounded-3xl border-2 border-yellow-100 hover:border-yellow-500 transition-all shadow-lg hover:shadow-xl group relative overflow-hidden">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-yellow-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    </div>

                    <div class="relative z-10">
                        <div
                            class="w-20 h-20 bg-yellow-100 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Turnirlar</h2>
                        <p class="text-gray-500 mb-8">Katta sovrinlar uchun turnirlarda qatnashing.</p>

                        <Link :href="route('student.tournaments.index')"
                            class="block w-full py-4 bg-white border-2 border-yellow-500 text-yellow-700 font-bold rounded-xl hover:bg-yellow-50 transition-colors">
                        Turnirlarni ko'rish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Active Battle Banner -->
            <div v-if="activeBattle"
                class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white flex items-center justify-between shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center animate-pulse">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <h3 class="font-bold text-lg">Faol jangingiz mavjud!</h3>
                        <p class="text-purple-100 text-sm">Jangni davom ettirish uchun bosing.</p>
                    </div>
                </div>
                <Link :href="route('student.battles.show', activeBattle.id)"
                    class="px-6 py-2 bg-white text-purple-600 font-bold rounded-lg hover:bg-gray-100 transition-colors">
                Davom ettirish
                </Link>
            </div>
        </div>
    </StudentLayout>
</template>
