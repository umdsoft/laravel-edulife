<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import { Alert, Button, Card, FormField, Input, PageHeader, Select } from '@/Components/UI';
import { TicketIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/20/solid';

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

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const formatPrice = (price) => {
    if (!price || Number(price) === 0) return 'Bepul';
    return `${groupDigits(price)} so'm`;
};

const gradeOptions = Array.from({ length: 12 }, (_, i) => ({ value: i + 1, label: `${i + 1}-sinf` }));

const couponMessage = computed(() => (couponValidation.value.valid
    ? `${formatPrice(couponValidation.value.discount_amount)} chegirma qo'llandi`
    : couponValidation.value.message));
</script>

<template>
    <StudentLayout title="Ro'yxatdan o'tish">
        <Head :title="`Ro'yxatdan o'tish - ${olympiad.title}`" />

        <PageHeader title="Ro'yxatdan o'tish" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'Ro\'yxatdan o\'tish' },
        ]" />

        <div class="max-w-xl space-y-6">
            <Card>
                <form class="space-y-5" @submit.prevent="submit">
                    <!-- Olympiad Summary -->
                    <dl class="divide-y divide-border rounded-md bg-surface-2 px-4 text-sm">
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Olimpiada turi</dt>
                            <dd class="text-right font-medium text-fg">{{ olympiad.type }}</dd>
                        </div>
                        <div v-if="olympiad.stage" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Bosqich</dt>
                            <dd class="text-right font-medium text-fg">{{ olympiad.stage }}</dd>
                        </div>
                        <div v-if="olympiad.registration_end_at" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Ro'yxat tugashi</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ olympiad.registration_end_at }}</dd>
                        </div>
                    </dl>

                    <!-- Grade Level -->
                    <Select v-model="form.grade_level" label="Sinf" optional placeholder="Tanlang"
                        :options="gradeOptions" :error="form.errors.grade_level" />

                    <!-- Coupon Code -->
                    <FormField v-slot="{ id }" label="Chegirma kodi" optional :error="form.errors.coupon_code">
                        <div class="flex gap-2">
                            <Input :id="id" v-model="form.coupon_code" placeholder="Masalan: OLIMP2025" :leading-icon="TicketIcon"
                                autocapitalize="characters" autocomplete="off" class="flex-1" />
                            <Button variant="secondary" :loading="isValidatingCoupon" @click="validateCoupon">
                                Tekshirish
                            </Button>
                        </div>
                        <p v-if="couponValidation.checked" role="status"
                            :class="['mt-2 flex items-center gap-1.5 text-13', couponValidation.valid ? 'text-success' : 'text-danger']">
                            <CheckCircleIcon v-if="couponValidation.valid" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <XCircleIcon v-else class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ couponMessage }}
                        </p>
                    </FormField>

                    <!-- Price Summary -->
                    <dl class="space-y-2 border-t border-border pt-5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-fg-muted">Asl narx</dt>
                            <dd class="text-fg tabular-nums">{{ formatPrice(olympiad.registration_fee) }}</dd>
                        </div>
                        <div v-if="couponValidation.discount_amount > 0" class="flex justify-between gap-3">
                            <dt class="text-success">Chegirma</dt>
                            <dd class="text-success tabular-nums">−{{ formatPrice(couponValidation.discount_amount) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-border pt-3 text-base font-semibold">
                            <dt class="text-fg">Jami</dt>
                            <dd class="text-fg tabular-nums">{{ formatPrice(finalPrice) }}</dd>
                        </div>
                    </dl>

                    <!-- User Coins Info -->
                    <Alert v-if="userCoins > 0" variant="info" :title="`Sizda ${userCoins} coin bor`">
                        To'lov sahifasida coin'lardan foydalanishingiz mumkin.
                    </Alert>

                    <!-- Terms -->
                    <p class="text-13 text-fg-subtle">
                        Ro'yxatdan o'tish orqali siz foydalanish shartlari va
                        <Link :href="route('student.olympiads.show', olympiad.slug)"
                            class="focus-ring rounded-sm text-brand underline-offset-4 hover:underline">
                            olimpiada qoidalari</Link>ga rozilik bildirasiz.
                    </p>

                    <!-- Actions -->
                    <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                        <Button variant="secondary" :href="route('student.olympiads.show', olympiad.slug)">
                            Bekor qilish
                        </Button>
                        <Button type="submit" :loading="form.processing">
                            {{ finalPrice > 0 ? "To'lovga o'tish" : "Ro'yxatdan o'tish" }}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </StudentLayout>
</template>
