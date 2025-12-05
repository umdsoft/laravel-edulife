<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    olympiad: Object,
    userCoins: Number,
});

const form = useForm({
    coupon_code: '',
    school_id: '',
    grade_level: '',
});

const couponValidation = ref({
    checked: false,
    valid: false,
    discount_amount: 0,
    message: '',
});

const isValidatingCoupon = ref(false);

const finalPrice = computed(() => {
    const base = props.olympiad.registration_fee || 0;
    return Math.max(0, base - couponValidation.value.discount_amount);
});

const validateCoupon = async () => {
    if (!form.coupon_code.trim()) {
        couponValidation.value = { checked: true, valid: false, message: 'Kupon kodini kiriting', discount_amount: 0 };
        return;
    }

    isValidatingCoupon.value = true;
    try {
        const response = await fetch(route('student.olympiads.coupon.validate', props.olympiad.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ code: form.coupon_code }),
        });
        
        const data = await response.json();
        couponValidation.value = {
            checked: true,
            valid: data.valid,
            discount_amount: data.discount_amount || 0,
            message: data.valid ? `${data.discount_amount} so'm chegirma` : data.message,
        };
    } catch (error) {
        couponValidation.value = { checked: true, valid: false, message: 'Xatolik yuz berdi', discount_amount: 0 };
    } finally {
        isValidatingCoupon.value = false;
    }
};

const submit = () => {
    form.post(route('student.olympiads.register.store', props.olympiad.slug));
};

const formatPrice = (price) => {
    if (!price || price === 0) return 'Bepul';
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};
</script>

<template>
    <StudentLayout>
        <Head :title="`Ro'yxatdan o'tish - ${olympiad.title}`" />
        
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
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6 text-white text-center">
                        <h1 class="text-2xl font-bold mb-2">Ro'yxatdan o'tish</h1>
                        <p class="opacity-80">{{ olympiad.title }}</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Olympiad Summary -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Olimpiada turi:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.type }}</span>
                            </div>
                            <div v-if="olympiad.stage" class="flex justify-between">
                                <span class="text-gray-500">Bosqich:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.stage }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Ro'yxat tugashi:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ olympiad.registration_end_at }}</span>
                            </div>
                        </div>

                        <!-- Grade Level -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sinf darajasi (ixtiyoriy)
                            </label>
                            <select 
                                v-model="form.grade_level"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">Tanlang...</option>
                                <option v-for="grade in 12" :key="grade" :value="grade">{{ grade }}-sinf</option>
                            </select>
                        </div>

                        <!-- Coupon Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Chegirma kodi (ixtiyoriy)
                            </label>
                            <div class="flex gap-3">
                                <input 
                                    v-model="form.coupon_code"
                                    type="text"
                                    placeholder="COUPON2024"
                                    class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-purple-500 focus:border-purple-500 uppercase">
                                <button 
                                    type="button"
                                    @click="validateCoupon"
                                    :disabled="isValidatingCoupon"
                                    class="px-6 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-medium hover:bg-gray-200 transition-colors disabled:opacity-50">
                                    {{ isValidatingCoupon ? '...' : 'Tekshirish' }}
                                </button>
                            </div>
                            <p v-if="couponValidation.checked" 
                                :class="['mt-2 text-sm', couponValidation.valid ? 'text-green-600' : 'text-red-600']">
                                {{ couponValidation.message }}
                            </p>
                        </div>

                        <!-- Price Summary -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Asl narx:</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatPrice(olympiad.registration_fee) }}</span>
                                </div>
                                <div v-if="couponValidation.discount_amount > 0" class="flex justify-between text-sm">
                                    <span class="text-green-600">Chegirma:</span>
                                    <span class="text-green-600">-{{ formatPrice(couponValidation.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <span class="text-gray-900 dark:text-white">Jami:</span>
                                    <span class="text-purple-600">{{ formatPrice(finalPrice) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- User Coins Info -->
                        <div v-if="userCoins > 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">🪙</span>
                                <div>
                                    <p class="font-medium text-amber-800 dark:text-amber-200">Sizda {{ userCoins }} coin bor</p>
                                    <p class="text-sm text-amber-600 dark:text-amber-300">To'lov sahifasida coin'lardan foydalanishingiz mumkin</p>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-sm text-gray-600 dark:text-gray-400">
                            <p>
                                Ro'yxatdan o'tish orqali siz 
                                <a href="#" class="text-purple-600 underline">foydalanish shartlari</a> va 
                                <a href="#" class="text-purple-600 underline">olimpiada qoidalari</a>ga rozilik bildirasiz.
                            </p>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg disabled:opacity-50">
                            {{ form.processing ? 'Yuklanmoqda...' : (finalPrice > 0 ? "To'lovga o'tish" : "Ro'yxatdan o'tish") }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
