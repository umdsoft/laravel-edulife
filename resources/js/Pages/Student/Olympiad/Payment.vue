<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, computed } from 'vue';
import { Button, Card, FormField, PageHeader } from '@/Components/UI';
import { BanknotesIcon, CreditCardIcon, DevicePhoneMobileIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

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

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const formatPrice = (price) => {
    return `${groupDigits(price)} so'm`;
};

const paymentMethods = [
    { id: 'card', name: 'Bank kartasi', icon: CreditCardIcon, description: 'Visa, Mastercard, Humo, Uzcard' },
    { id: 'payme', name: 'Payme', icon: DevicePhoneMobileIcon, description: "Payme ilovasi orqali" },
    { id: 'click', name: 'Click', icon: DevicePhoneMobileIcon, description: "Click ilovasi orqali" },
];
</script>

<template>
    <StudentLayout title="To'lov">
        <Head :title="`To'lov - ${olympiad.title}`" />

        <PageHeader title="To'lov" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'To\'lov' },
        ]" />

        <div class="max-w-xl space-y-6">
            <form class="space-y-6" @submit.prevent="submit">
                <!-- Order Summary -->
                <Card title="Buyurtma tafsilotlari">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-fg-muted">Asl narx</dt>
                            <dd class="text-fg tabular-nums">{{ formatPrice(registration.original_price) }}</dd>
                        </div>
                        <div v-if="registration.discount_amount > 0" class="flex justify-between gap-3">
                            <dt class="text-success">Chegirma</dt>
                            <dd class="text-success tabular-nums">−{{ formatPrice(registration.discount_amount) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-border pt-3 text-base font-semibold">
                            <dt class="text-fg">Jami</dt>
                            <dd class="text-fg tabular-nums">{{ formatPrice(registration.final_price) }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <div class="space-y-6">
                        <!-- Use Coins -->
                        <div v-if="userCoins > 0" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="coins-amount" class="flex items-center gap-2 text-sm font-medium text-fg">
                                    <BanknotesIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                                    Coin'lardan foydalanish
                                </label>
                                <span class="text-13 text-fg-subtle tabular-nums">{{ userCoins }} coin mavjud</span>
                            </div>
                            <input id="coins-amount" v-model.number="form.coins_amount" type="range" :min="0"
                                :max="maxCoinsUsable" class="focus-ring h-10 w-full cursor-pointer accent-brand-600"
                                :aria-valuetext="`${form.coins_amount} coin`">
                            <div class="flex justify-between text-sm tabular-nums">
                                <span class="text-fg-muted">{{ form.coins_amount }} coin</span>
                                <span class="font-medium text-success">−{{ formatPrice(coinsDiscount) }}</span>
                            </div>
                            <p v-if="form.errors.coins_amount" class="text-13 text-danger">{{ form.errors.coins_amount }}</p>
                        </div>

                        <!-- Payment Methods -->
                        <FormField v-if="amountToPay > 0" as="fieldset" label="To'lov usuli"
                            :error="form.errors.payment_method">
                            <div class="space-y-2">
                                <label v-for="method in paymentMethods" :key="method.id" :class="[
                                    'flex min-h-[56px] cursor-pointer items-center gap-4 rounded-md border p-4 transition-colors duration-150',
                                    'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500 has-[:focus-visible]:ring-offset-2',
                                    form.payment_method === method.id
                                        ? 'border-brand-200 bg-brand-50'
                                        : 'border-border bg-surface hover:bg-surface-2',
                                ]">
                                    <input v-model="form.payment_method" type="radio" name="payment_method"
                                        :value="method.id" class="sr-only">
                                    <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-md',
                                        form.payment_method === method.id ? 'bg-surface text-brand' : 'bg-surface-2 text-fg-subtle']">
                                        <component :is="method.icon" class="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-medium text-fg">{{ method.name }}</span>
                                        <span class="block text-13 text-fg-muted">{{ method.description }}</span>
                                    </span>
                                    <span :class="['flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2',
                                        form.payment_method === method.id ? 'border-brand-600' : 'border-border-strong']"
                                        aria-hidden="true">
                                        <span v-if="form.payment_method === method.id"
                                            class="h-2.5 w-2.5 rounded-full bg-brand-600" />
                                    </span>
                                </label>
                            </div>
                        </FormField>

                        <!-- Amount to Pay -->
                        <div class="flex items-center justify-between gap-3 rounded-md bg-surface-2 p-4">
                            <span class="text-sm text-fg-muted">To'lanadigan summa</span>
                            <span class="text-2xl font-semibold tracking-tight text-fg tabular-nums">
                                {{ formatPrice(amountToPay) }}
                            </span>
                        </div>

                        <!-- Security Note -->
                        <p class="flex items-start gap-2 text-13 text-fg-subtle">
                            <LockClosedIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            Barcha to'lovlar xavfsiz va shifrlangan. Karta ma'lumotlaringiz saqlanmaydi.
                        </p>
                    </div>

                    <template #footer>
                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <Button variant="secondary" :href="route('student.olympiads.show', olympiad.slug)">
                                Bekor qilish
                            </Button>
                            <Button type="submit" :loading="form.processing">
                                {{ amountToPay > 0 ? "To'lash" : "Tasdiqlash" }}
                            </Button>
                        </div>
                    </template>
                </Card>
            </form>
        </div>
    </StudentLayout>
</template>
