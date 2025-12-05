<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    packages: {
        type: Array,
        default: () => []
    },
    balance: {
        type: Number,
        default: 0
    },
    level: {
        type: Number,
        default: 1
    }
});

const selectedPackage = ref(null);
const processing = ref(false);
const showEarnGuide = ref(true);

const purchaseCounts = ref({});

onMounted(() => {
    props.packages.forEach(pkg => {
        purchaseCounts.value[pkg.id] = Math.floor(Math.random() * 400) + 150;
    });
});

const formatPrice = (price) => new Intl.NumberFormat('uz-UZ').format(price || 0);
const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);

const purchase = (pkg) => {
    if (processing.value) return;
    processing.value = true;
    selectedPackage.value = pkg.id;
    router.visit(`/student/payment/checkout?package=${pkg.id}&type=coin`);
};

// Coin earning methods
const earnMethods = [
    { icon: '📚', title: 'Dars tugatish', coins: '10-50', desc: 'Har bir darsni tugatganingizda', color: 'bg-blue-50 border-blue-200' },
    { icon: '✅', title: 'Test topshirish', coins: '20-100', desc: 'Testlarni muvaffaqiyatli topshiring', color: 'bg-green-50 border-green-200' },
    { icon: '🔥', title: 'Kunlik streak', coins: '5-50', desc: 'Har kuni platformaga kiring', color: 'bg-orange-50 border-orange-200' },
    { icon: '🏆', title: 'Kurs tugatish', coins: '100-500', desc: 'To\'liq kursni yakunlang', color: 'bg-purple-50 border-purple-200' },
    { icon: '🎯', title: 'Haftalik maqsad', coins: '50-200', desc: 'Haftalik maqsadlarga erishing', color: 'bg-pink-50 border-pink-200' },
    { icon: '👥', title: 'Do\'st taklif qilish', coins: '100', desc: 'Yangi foydalanuvchi taklif qiling', color: 'bg-teal-50 border-teal-200' },
    { icon: '⚔️', title: 'Turnir g\'olabi', coins: '200-1000', desc: 'Turnirlarda g\'olib bo\'ling', color: 'bg-red-50 border-red-200' },
    { icon: '🥇', title: 'Olimpiada', coins: '500-2000', desc: 'Olimpiadalarda qatnashing', color: 'bg-yellow-50 border-yellow-200' },
];

// Coin spending methods
const spendMethods = [
    { icon: '📖', title: 'Premium kurslar', desc: 'Eksklyuziv kurslarga kirish' },
    { icon: '🔬', title: 'Laboratoriya', desc: 'Maxsus tajribalar ochish' },
    { icon: '💡', title: 'Maslahatlar', desc: 'Test javoblariga maslahat' },
    { icon: '⏰', title: 'Qo\'shimcha vaqt', desc: 'Test uchun qo\'shimcha vaqt' },
    { icon: '🎨', title: 'Avatar va badge', desc: 'Profilingizni bezating' },
    { icon: '🎫', title: 'Musobaqalar', desc: 'Maxsus musobaqalarga ro\'yxatdan o\'ting' },
];
</script>

<template>

    <Head title="Coin Sotib Olish - EDULIFE" />

    <StudentLayout>
        <div class="space-y-8">

            <!-- Hero Section -->
            <div
                class="bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 rounded-3xl p-8 border border-amber-100">
                <div class="max-w-4xl mx-auto text-center">
                    <!-- Title -->
                    <div class="flex items-center justify-center gap-3 mb-3">
                        <span class="text-4xl">🪙</span>
                        <h1 class="text-3xl font-bold text-gray-900">Coin Sotib Olish</h1>
                    </div>
                    <p class="text-gray-600 mb-6">Premium kurslar va maxsus imkoniyatlar uchun coin sotib oling</p>

                    <!-- Balance + Stats in one row -->
                    <div class="flex flex-wrap items-center justify-center gap-6">
                        <!-- Current Balance -->
                        <div
                            class="flex items-center gap-3 bg-white rounded-2xl px-6 py-3 shadow-sm border border-amber-200">
                            <div class="w-10 h-10 bg-amber-400 rounded-full flex items-center justify-center">
                                <span class="text-xl">🪙</span>
                            </div>
                            <div class="text-left">
                                <p class="text-xs text-gray-500">Balansingiz</p>
                                <p class="text-xl font-bold text-amber-600">{{ formatNumber(balance) }} coin</p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 text-sm">
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span
                                    class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xs">✓</span>
                                <strong>10K+</strong> xarid
                            </span>
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span class="text-yellow-500">★</span>
                                <strong>4.9</strong> baho
                            </span>
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span
                                    class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xs">🔒</span>
                                <strong>100%</strong> xavfsiz
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages Grid - 4 columns, uniform cards -->
            <div v-if="packages.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div v-for="pkg in packages" :key="pkg.id" class="relative">
                    <div :class="[
                        'h-full bg-white rounded-2xl overflow-hidden transition-all duration-300 flex flex-col',
                        pkg.is_popular
                            ? 'ring-2 ring-purple-500 shadow-xl shadow-purple-100'
                            : 'border border-gray-200 hover:border-purple-200 hover:shadow-lg'
                    ]">
                        <!-- Header Badge -->
                        <div v-if="pkg.is_popular" class="bg-purple-500 text-white text-center py-2 text-sm font-bold">
                            ⭐ ENG OMMABOP TANLOV
                        </div>
                        <div v-else-if="pkg.badge" class="bg-amber-500 text-white text-center py-2 text-sm font-bold">
                            {{ pkg.badge }}
                        </div>
                        <div v-else class="bg-gray-100 text-gray-500 text-center py-2 text-sm font-medium">
                            Coin paketi
                        </div>

                        <div class="p-5 flex-1 flex flex-col">
                            <!-- Coin visual - all same size -->
                            <div class="flex justify-center mb-4">
                                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                                    <span class="text-3xl">🪙</span>
                                </div>
                            </div>

                            <!-- Package name - all same size -->
                            <h3 class="text-center text-lg font-bold text-gray-900 mb-2">{{ pkg.name }}</h3>

                            <!-- Coins amount - all same size -->
                            <div class="text-center mb-4">
                                <span class="text-3xl font-bold text-gray-900">{{ formatNumber(pkg.coins) }}</span>
                                <span class="text-gray-500 ml-1">coin</span>

                                <!-- Bonus -->
                                <div v-if="pkg.bonus_coins > 0" class="mt-2">
                                    <span
                                        class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                                        +{{ formatNumber(pkg.bonus_coins) }} bonus 🎁
                                    </span>
                                    <p class="text-xs text-gray-400 mt-1">Jami: {{ formatNumber(pkg.total_coins) }} coin
                                    </p>
                                </div>
                                <div v-else class="mt-2">
                                    <span class="inline-block text-xs text-gray-400 py-1">—</span>
                                </div>
                            </div>

                            <!-- Price section -->
                            <div class="text-center py-4 border-t border-b border-gray-100 mb-4">
                                <p class="text-xl font-bold text-gray-900">
                                    {{ formatPrice(pkg.price) }}
                                    <span class="text-sm font-normal text-gray-500">so'm</span>
                                </p>
                                <p class="text-xs text-green-600 font-medium mt-1">
                                    ≈ {{ Math.round(pkg.price / pkg.total_coins) }} so'm / coin
                                </p>
                            </div>

                            <!-- Social proof -->
                            <div class="flex items-center justify-center gap-2 mb-4">
                                <div class="flex -space-x-1.5">
                                    <div class="w-5 h-5 rounded-full bg-blue-400 border-2 border-white"></div>
                                    <div class="w-5 h-5 rounded-full bg-green-400 border-2 border-white"></div>
                                    <div class="w-5 h-5 rounded-full bg-purple-400 border-2 border-white"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ purchaseCounts[pkg.id] }}+ kishi sotib
                                    oldi</span>
                            </div>

                            <!-- Features list - all show 3 items for consistency -->
                            <ul class="space-y-2 mb-5 text-sm flex-1">
                                <li class="flex items-center gap-2 text-gray-600">
                                    <span class="text-green-500">✓</span> Darhol aktivatsiya
                                </li>
                                <li class="flex items-center gap-2 text-gray-600">
                                    <span class="text-green-500">✓</span> Xavfsiz to'lov
                                </li>
                                <li class="flex items-center gap-2 text-gray-600">
                                    <span class="text-green-500">✓</span> 24/7 qo'llab-quvvatlash
                                </li>
                            </ul>

                            <!-- CTA Button - ALL SAME COLOR -->
                            <button @click="purchase(pkg)" :disabled="processing && selectedPackage === pkg.id"
                                class="w-full py-3 rounded-xl font-semibold transition-all bg-purple-500 hover:bg-purple-600 text-white">
                                <span v-if="processing && selectedPackage === pkg.id">Yuklanmoqda...</span>
                                <span v-else>Sotib olish →</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-gray-50 rounded-2xl">
                <span class="text-5xl">📦</span>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Coin paketlari mavjud emas</h3>
            </div>

            <!-- ============================================ -->
            <!-- COIN YIĞISH YO'RIQNOMASI -->
            <!-- ============================================ -->

            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <button @click="showEarnGuide = !showEarnGuide"
                    class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">💰</span>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Coinlarni bepul yig'ish yo'llari</h2>
                            <p class="text-sm text-gray-500">Sotib olmasdan coin yig'ish imkoniyatlari</p>
                        </div>
                    </div>
                    <svg :class="['w-5 h-5 text-gray-400 transition-transform', showEarnGuide ? 'rotate-180' : '']"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-show="showEarnGuide" class="border-t border-gray-200">
                    <div class="p-5">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span>🎯</span> Coin yig'ish usullari
                        </h3>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div v-for="method in earnMethods" :key="method.title"
                                :class="['rounded-xl p-3 border', method.color]">
                                <div class="flex items-start gap-2">
                                    <span class="text-xl">{{ method.icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="font-semibold text-gray-900 text-sm truncate">{{ method.title }}
                                            </h4>
                                            <span
                                                class="text-xs font-bold text-amber-600 bg-amber-100 px-1.5 py-0.5 rounded whitespace-nowrap">
                                                +{{ method.coins }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ method.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200"></div>

                    <div class="p-5 bg-gray-50">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span>🛒</span> Coinlarni nimaga sarflash mumkin?
                        </h3>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="method in spendMethods" :key="method.title"
                                class="flex items-center gap-3 bg-white rounded-xl p-3 border border-gray-200">
                                <span class="text-xl">{{ method.icon }}</span>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-sm">{{ method.title }}</h4>
                                    <p class="text-xs text-gray-500">{{ method.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 border-t border-gray-200">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span>💡</span> Foydali maslahatlar
                        </h3>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600">Har kuni platformaga kirib <strong>streak</strong>ingizni
                                    saqlang</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600">Testlarni <strong>yuqori ball</strong> bilan topshiring</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600">Do'stlaringizni <strong>taklif qiling</strong> - 100 coin</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600"><strong>Turnirlarda</strong> qatnashing va g'olib bo'ling</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600">Kurslarni <strong>to'liq tugatib</strong> sertifikat oling</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-green-500 mt-0.5">✓</span>
                                <p class="text-gray-600"><strong>Haftalik maqsadlarga</strong> ering</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonials -->
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h2 class="font-bold text-gray-900 text-center mb-5">💬 Foydalanuvchilar fikrlari</h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-0.5 text-yellow-400 text-sm mb-2">★★★★★</div>
                        <p class="text-gray-600 text-sm mb-3">"Premium kurslar juda foydali! Bilimlarim sezilarli
                            oshdi."</p>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                A</div>
                            <span class="text-sm font-medium text-gray-900">Aziza M.</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-0.5 text-yellow-400 text-sm mb-2">★★★★★</div>
                        <p class="text-gray-600 text-sm mb-3">"Laboratoriya tajribalari juda ajoyib! Tavsiya qilaman."
                        </p>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                J</div>
                            <span class="text-sm font-medium text-gray-900">Jahongir K.</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-0.5 text-yellow-400 text-sm mb-2">★★★★★</div>
                        <p class="text-gray-600 text-sm mb-3">"Bonus coinlar juda yoqdi! Qo'shimcha foyda oldim."</p>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                N</div>
                            <span class="text-sm font-medium text-gray-900">Nodira S.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trust & Security -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🔒</span>
                        <div>
                            <p class="font-semibold text-gray-900">100% Xavfsiz To'lov</p>
                            <p class="text-xs text-gray-500">256-bit SSL shifrlash bilan himoyalangan</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <span class="text-xl">💳</span>
                            <p class="text-xs text-gray-500 mt-1">Payme</p>
                        </div>
                        <div class="text-center">
                            <span class="text-xl">📱</span>
                            <p class="text-xs text-gray-500 mt-1">Click</p>
                        </div>
                        <div class="text-center">
                            <span class="text-xl">🏦</span>
                            <p class="text-xs text-gray-500 mt-1">Bank karta</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
