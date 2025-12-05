<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    olympiad: Object,
    registration: Object,
    canRegister: Object,
});

const activeTab = ref('about');
const isRegistering = ref(false);

const getStatusBadge = (status) => {
    const badges = {
        'upcoming': { label: 'Tez kunda', class: 'bg-blue-500 text-white', icon: '📅' },
        'registration_open': { label: "Ro'yxat ochiq", class: 'bg-green-500 text-white', icon: '✅' },
        'live': { label: 'Jonli', class: 'bg-red-500 text-white animate-pulse', icon: '🔴' },
        'grading': { label: 'Baholanmoqda', class: 'bg-yellow-500 text-white', icon: '⏳' },
        'completed': { label: 'Yakunlangan', class: 'bg-gray-500 text-white', icon: '🏁' },
    };
    return badges[status] || badges['upcoming'];
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Bepul';
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};

const getSectionIcon = (type) => {
    const icons = {
        'test': '📝',
        'listening': '🎧',
        'reading': '📖',
        'writing': '✍️',
        'speaking': '🎤',
        'coding': '💻',
        'math': '🔢',
    };
    return icons[type] || '📋';
};

const handleRegister = () => {
    router.visit(route('student.olympiads.register', props.olympiad.slug));
};

const handleStartExam = () => {
    router.visit(route('student.olympiads.preflight', props.olympiad.slug));
};
</script>

<template>
    <StudentLayout>
        <Head :title="olympiad.title" />
        
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Hero Banner -->
            <div class="relative h-72 md:h-96 bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-700 overflow-hidden">
                <img 
                    v-if="olympiad.banner_image" 
                    :src="olympiad.banner_image" 
                    :alt="olympiad.title"
                    class="absolute inset-0 w-full h-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                
                <div class="absolute bottom-0 left-0 right-0 p-6 md:p-12">
                    <div class="max-w-7xl mx-auto">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span :class="['px-4 py-1.5 rounded-full text-sm font-semibold', getStatusBadge(olympiad.status).class]">
                                {{ getStatusBadge(olympiad.status).icon }} {{ getStatusBadge(olympiad.status).label }}
                            </span>
                            <span class="px-4 py-1.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur">
                                {{ olympiad.type }}
                            </span>
                            <span v-if="olympiad.stage" class="px-4 py-1.5 bg-white/20 text-white rounded-full text-sm font-medium backdrop-blur">
                                {{ olympiad.stage }}
                            </span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                            {{ olympiad.title }}
                        </h1>
                        <p v-if="olympiad.series" class="text-lg text-white/80">
                            {{ olympiad.series }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Tabs -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                            <div class="flex border-b border-gray-200 dark:border-gray-700">
                                <button 
                                    @click="activeTab = 'about'"
                                    :class="['flex-1 py-4 text-center font-medium transition-colors', 
                                        activeTab === 'about' 
                                            ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50 dark:bg-purple-900/20' 
                                            : 'text-gray-600 dark:text-gray-400 hover:text-purple-600']">
                                    📋 Ma'lumot
                                </button>
                                <button 
                                    @click="activeTab = 'sections'"
                                    :class="['flex-1 py-4 text-center font-medium transition-colors', 
                                        activeTab === 'sections' 
                                            ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50 dark:bg-purple-900/20' 
                                            : 'text-gray-600 dark:text-gray-400 hover:text-purple-600']">
                                    🧩 Bo'limlar
                                </button>
                                <button 
                                    @click="activeTab = 'prizes'"
                                    :class="['flex-1 py-4 text-center font-medium transition-colors', 
                                        activeTab === 'prizes' 
                                            ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50 dark:bg-purple-900/20' 
                                            : 'text-gray-600 dark:text-gray-400 hover:text-purple-600']">
                                    🏆 Sovrinlar
                                </button>
                            </div>

                            <div class="p-6">
                                <!-- About Tab -->
                                <div v-if="activeTab === 'about'">
                                    <div v-if="olympiad.description" class="prose dark:prose-invert max-w-none mb-6" v-html="olympiad.description"></div>
                                    
                                    <div v-if="olympiad.rules" class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                        <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">📜 Qoidalar</h3>
                                        <div class="text-sm text-amber-700 dark:text-amber-300" v-html="olympiad.rules"></div>
                                    </div>
                                </div>

                                <!-- Sections Tab -->
                                <div v-if="activeTab === 'sections'" class="space-y-4">
                                    <div 
                                        v-for="(section, index) in olympiad.sections" 
                                        :key="section.id"
                                        class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                        <div class="w-12 h-12 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-2xl">
                                            {{ getSectionIcon(section.type) }}
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h4>
                                            <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                <span>⏱️ {{ section.duration_minutes }} daqiqa</span>
                                                <span>📊 {{ section.max_points }} ball</span>
                                                <span>💯 {{ section.weight_percent }}% vazn</span>
                                            </div>
                                        </div>
                                        <div class="text-2xl font-bold text-gray-300 dark:text-gray-600">
                                            {{ String(index + 1).padStart(2, '0') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Prizes Tab -->
                                <div v-if="activeTab === 'prizes'" class="space-y-6">
                                    <div v-if="olympiad.prize_distribution" class="grid gap-4 sm:grid-cols-3">
                                        <div class="text-center p-6 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-2xl text-white">
                                            <div class="text-4xl mb-2">🥇</div>
                                            <div class="font-bold text-xl">1-o'rin</div>
                                            <div class="text-2xl font-bold">{{ formatPrice(olympiad.prize_distribution['1']) }}</div>
                                        </div>
                                        <div class="text-center p-6 bg-gradient-to-br from-gray-300 to-gray-400 rounded-2xl text-white">
                                            <div class="text-4xl mb-2">🥈</div>
                                            <div class="font-bold text-xl">2-o'rin</div>
                                            <div class="text-2xl font-bold">{{ formatPrice(olympiad.prize_distribution['2']) }}</div>
                                        </div>
                                        <div class="text-center p-6 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl text-white">
                                            <div class="text-4xl mb-2">🥉</div>
                                            <div class="font-bold text-xl">3-o'rin</div>
                                            <div class="text-2xl font-bold">{{ formatPrice(olympiad.prize_distribution['3']) }}</div>
                                        </div>
                                    </div>

                                    <div v-if="olympiad.reward_config" class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                        <h4 class="font-semibold text-purple-800 dark:text-purple-200 mb-3">🎁 Qo'shimcha mukofotlar</h4>
                                        <ul class="space-y-2 text-sm text-purple-700 dark:text-purple-300">
                                            <li v-if="olympiad.reward_config.coins_per_correct">✅ Har bir to'g'ri javob: {{ olympiad.reward_config.coins_per_correct }} coin</li>
                                            <li v-if="olympiad.reward_config.top_10_coins">🏅 Top 10 uchun: {{ olympiad.reward_config.top_10_coins }} coin</li>
                                            <li v-if="olympiad.reward_config.advancement">📈 Keyingi bosqichga o'tish imkoniyati</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Registration Card -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-24">
                            <!-- Price -->
                            <div class="text-center mb-6">
                                <div class="text-3xl font-bold" :class="olympiad.registration_fee ? 'text-purple-600' : 'text-green-600'">
                                    {{ formatPrice(olympiad.registration_fee) }}
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Ro'yxatdan o'tish narxi</p>
                            </div>

                            <!-- Schedule -->
                            <div class="space-y-3 mb-6 text-sm">
                                <div v-if="olympiad.registration_end_at" class="flex justify-between">
                                    <span class="text-gray-500">Ro'yxat tugashi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.registration_end_at }}</span>
                                </div>
                                <div v-if="olympiad.olympiad_start_at" class="flex justify-between">
                                    <span class="text-gray-500">Olimpiada boshlanishi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.olympiad_start_at }}</span>
                                </div>
                                <div v-if="olympiad.total_duration" class="flex justify-between">
                                    <span class="text-gray-500">Davomiyligi:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.total_duration }} daqiqa</span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div v-if="registration">
                                <!-- Already registered -->
                                <div v-if="registration.status === 'confirmed'" class="space-y-4">
                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
                                        <span class="text-green-600 dark:text-green-400 font-medium">✅ Ro'yxatdan o'tgansiz</span>
                                    </div>
                                    <button 
                                        v-if="olympiad.status === 'live'"
                                        @click="handleStartExam"
                                        class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                                        🚀 Imtihonni boshlash
                                    </button>
                                    
                                    <!-- Demo Purchase -->
                                    <div v-if="olympiad.demo_price > 0 && !registration.demo_purchased" class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                        <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">Demo testni sinab ko'ring</p>
                                        <Link 
                                            :href="route('student.olympiads.show', olympiad.slug) + '#demo'"
                                            class="block text-center py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                            Demo sotib olish ({{ formatPrice(olympiad.demo_price) }})
                                        </Link>
                                    </div>
                                </div>

                                <div v-else-if="registration.status === 'pending_payment'">
                                    <Link 
                                        :href="route('student.olympiads.payment', { slug: olympiad.slug, registration: registration.id })"
                                        class="block w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-bold text-lg text-center hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg">
                                        💳 To'lovni yakunlash
                                    </Link>
                                </div>
                            </div>

                            <div v-else-if="canRegister.can_register">
                                <button 
                                    @click="handleRegister"
                                    :disabled="isRegistering"
                                    class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50">
                                    {{ isRegistering ? 'Yuklanmoqda...' : "Ro'yxatdan o'tish" }}
                                </button>
                            </div>

                            <div v-else class="text-center">
                                <p class="text-gray-500 dark:text-gray-400 text-sm">
                                    {{ canRegister.reason === 'registration_closed' ? "Ro'yxat yopilgan" : 
                                       canRegister.reason === 'max_participants_reached' ? "Joylar tugagan" :
                                       canRegister.reason === 'registration_not_started' ? "Ro'yxat hali boshlanmagan" :
                                       "Ro'yxatdan o'tish imkonsiz" }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
