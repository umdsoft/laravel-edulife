<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    olympiad: Object,
    registration: Object,
    userCoins: Number,
    coinsExchangeRate: Number, // 1 coin = X so'm
});

const form = useForm({
    payment_method: 'card',
    coins_amount: 0,
});

const maxCoinsUsable = computed(() => {
    // Can't use more coins than needed or than user has
    const maxNeeded = Math.ceil(props.registration.final_price / (props.coinsExchangeRate / 100));
    return Math.min(props.userCoins, maxNeeded);
});

const coinsDiscount = computed(() => {
    return Math.floor(form.coins_amount * (props.coinsExchangeRate / 100));
});

const amountToPay = computed(() => {
    return Math.max(0, props.registration.final_price - coinsDiscount.value);
});

const submit = () => {
    form.post(route('student.olympiads.payment.process', { 
        slug: props.olympiad.slug, 
        registration: props.registration.id 
    }));
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};

const paymentMethods = [
    { id: 'card', name: 'Bank kartasi', icon: '💳', description: 'Visa, Mastercard, Humo, Uzcard' },
    { id: 'payme', name: 'Payme', icon: '📱', description: "Payme ilovasi orqali" },
    { id: 'click', name: 'Click', icon: '📲', description: "Click ilovasi orqali" },
];
</script>

<template>
    <StudentLayout>
        <Head :title="`To'lov - ${olympiad.title}`" />
        
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
            <div class="max-w-2xl mx-auto px-4">
                <!-- Breadcrumb -->
                <div class="mb-6">
                    <Link 
                        :href="route('student.olympiads.show', olympiad.slug)"
                        class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                        ← {{ olympiad.title }}ga qaytish
                    </Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-white text-center">
                        <h1 class="text-2xl font-bold mb-2">💳 To'lov</h1>
                        <p class="opacity-80">{{ olympiad.title }}</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Order Summary -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 space-y-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Buyurtma tafsilotlari</h3>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Asl narx:</span>
                                <span class="text-gray-900 dark:text-white">{{ formatPrice(registration.original_price) }}</span>
                            </div>
                            <div v-if="registration.discount_amount > 0" class="flex justify-between text-sm">
                                <span class="text-green-600">Chegirma:</span>
                                <span class="text-green-600">-{{ formatPrice(registration.discount_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-lg border-t border-gray-200 dark:border-gray-600 pt-3">
                                <span class="font-semibold text-gray-900 dark:text-white">Jami:</span>
                                <span class="font-bold text-purple-600">{{ formatPrice(registration.final_price) }}</span>
                            </div>
                        </div>

                        <!-- Use Coins -->
                        <div v-if="userCoins > 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">🪙</span>
                                    <span class="font-medium text-amber-800 dark:text-amber-200">Coin'lardan foydalanish</span>
                                </div>
                                <span class="text-sm text-amber-600">{{ userCoins }} coin mavjud</span>
                            </div>
                            <div class="space-y-2">
                                <input 
                                    type="range"
                                    v-model.number="form.coins_amount"
                                    :min="0"
                                    :max="maxCoinsUsable"
                                    class="w-full">
                                <div class="flex justify-between text-sm">
                                    <span class="text-amber-600">{{ form.coins_amount }} coin</span>
                                    <span class="text-green-600 font-medium">-{{ formatPrice(coinsDiscount) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div v-if="amountToPay > 0">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">To'lov usulini tanlang</h3>
                            <div class="space-y-3">
                                <label 
                                    v-for="method in paymentMethods" 
                                    :key="method.id"
                                    :class="[
                                        'flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                        form.payment_method === method.id 
                                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' 
                                            : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'
                                    ]">
                                    <input 
                                        type="radio"
                                        v-model="form.payment_method"
                                        :value="method.id"
                                        class="sr-only">
                                    <span class="text-3xl">{{ method.icon }}</span>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ method.name }}</div>
                                        <div class="text-sm text-gray-500">{{ method.description }}</div>
                                    </div>
                                    <div 
                                        :class="[
                                            'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                                            form.payment_method === method.id 
                                                ? 'border-purple-500 bg-purple-500' 
                                                : 'border-gray-300'
                                        ]">
                                        <span v-if="form.payment_method === method.id" class="text-white text-xs">✓</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Amount to Pay -->
                        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-6 text-center text-white">
                            <p class="text-sm opacity-80 mb-1">To'lanadigan summa</p>
                            <p class="text-4xl font-bold">{{ formatPrice(amountToPay) }}</p>
                        </div>

                        <!-- Security Note -->
                        <div class="flex items-start gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span class="text-xl">🔒</span>
                            <p>Barcha to'lovlar xavfsiz va shifrlangan. Karta ma'lumotlaringiz saqlanmaydi.</p>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-bold text-lg hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg disabled:opacity-50">
                            {{ form.processing ? 'Yuklanmoqda...' : (amountToPay > 0 ? "To'lash" : "Tasdiqlash") }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
