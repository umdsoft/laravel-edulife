<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    olympiad: Object,
    statistics: Object,
    sectionQueue: Array,
});

const isLoading = ref(false);

const finalize = async () => {
    if (!confirm('Natijalarni yakunlashni tasdiqlaysizmi? Bu amal qaytarilmaydi.')) return;
    
    isLoading.value = true;
    router.post(route('admin.olympiads.grading.finalize', props.olympiad.id), {}, {
        onFinish: () => isLoading.value = false,
    });
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Baholash - ${olympiad.title}`" />
        
        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <Link :href="route('admin.olympiads.show', olympiad.id)" class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                            ← {{ olympiad.title }}
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📝 Baholash paneli</h1>
                    </div>
                    <div class="flex gap-3">
                        <Link 
                            :href="route('admin.olympiads.grading.export', olympiad.id)"
                            class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 border border-gray-200 dark:border-gray-700">
                            📥 CSV eksport
                        </Link>
                        <button 
                            @click="finalize"
                            :disabled="isLoading || statistics.pending_grading > 0"
                            class="px-6 py-2 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ isLoading ? 'Yuklanmoqda...' : '✅ Natijalarni yakunlash' }}
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Jami urinishlar</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ statistics.total_attempts || 0 }}</p>
                            </div>
                            <span class="text-4xl">📋</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Auto baholangan</p>
                                <p class="text-3xl font-bold text-green-600">{{ statistics.auto_graded || 0 }}</p>
                            </div>
                            <span class="text-4xl">⚡</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Kutilmoqda</p>
                                <p class="text-3xl font-bold text-amber-600">{{ statistics.pending_grading || 0 }}</p>
                            </div>
                            <span class="text-4xl">⏳</span>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">O'rtacha ball</p>
                                <p class="text-3xl font-bold text-purple-600">{{ statistics.average_score || 0 }}%</p>
                            </div>
                            <span class="text-4xl">📊</span>
                        </div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white">Baholash progressi</h3>
                        <span class="text-sm text-gray-500">
                            {{ statistics.graded_percent || 0 }}% baholangan
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                        <div 
                            class="bg-gradient-to-r from-green-500 to-emerald-500 h-4 rounded-full transition-all"
                            :style="{ width: `${statistics.graded_percent || 0}%` }">
                        </div>
                    </div>
                </div>

                <!-- Section Queues -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white">📚 Bo'limlar bo'yicha navbat</h3>
                    </div>
                    
                    <div v-if="sectionQueue.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="item in sectionQueue" 
                            :key="item.section.id"
                            class="flex items-center justify-between p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-2xl">
                                    {{ item.section.type === 'writing' ? '✍️' : item.section.type === 'coding' ? '💻' : '📝' }}
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ item.section.title }}</h4>
                                    <p class="text-sm text-gray-500">{{ item.section.type }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold" :class="item.pending_count > 0 ? 'text-amber-600' : 'text-green-600'">
                                        {{ item.pending_count }}
                                    </div>
                                    <div class="text-xs text-gray-500">kutilmoqda</div>
                                </div>
                                <Link 
                                    v-if="item.pending_count > 0"
                                    :href="route('admin.olympiads.grading.section', { olympiad: olympiad.id, section: item.section.id })"
                                    class="px-6 py-2 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors">
                                    Baholashni boshlash
                                </Link>
                                <span v-else class="px-6 py-2 bg-green-100 text-green-700 rounded-xl font-medium">
                                    ✅ Yakunlangan
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="p-12 text-center">
                        <span class="text-5xl mb-4 block">✅</span>
                        <p class="text-gray-500">Manual baholash talab qiluvchi bo'limlar yo'q</p>
                    </div>
                </div>

                <!-- Auto-graded sections note -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        ℹ️ Test va ko'p tanlovli savollar avtomatik baholanadi. Faqat yozma va kod savollar manual baholashni talab qiladi.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
