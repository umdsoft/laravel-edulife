<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Button, Card, PageHeader } from '@/Components/UI';
import { CircleStackIcon } from '@heroicons/vue/20/solid';
import CheckoutForm from '@/Components/Payments/CheckoutForm.vue';

const props = defineProps({
    olympiad: Object,
    registration: Object,
    userCoins: { type: Number, default: 0 },
    coinsExchangeRate: { type: Number, default: 100 }, // so'm per coin
    // computed on the server from the final price
    coinsNeeded: { type: Number, default: 0 },
    // Payme / Click configured on the server
    onlinePaymentAvailable: { type: Boolean, default: false },
    paymentProviders: { type: Array, default: () => [] },
});

const form = useForm({ payment_method: 'coins' });
const enough = computed(() => props.userCoins >= props.coinsNeeded);

const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const formatPrice = (price) => `${groupDigits(price)} so'm`;

const submit = () => form.post(route('student.olympiads.payment.process', { slug: props.olympiad.slug, registration: props.registration.id }));
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
            <Card title="Buyurtma tafsilotlari">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-fg-muted">Asl narx</dt>
                        <dd class="tabular-nums text-fg">{{ formatPrice(registration.original_price) }}</dd>
                    </div>
                    <div v-if="registration.discount_amount > 0" class="flex justify-between gap-3">
                        <dt class="text-success">Chegirma</dt>
                        <dd class="tabular-nums text-success">−{{ formatPrice(registration.discount_amount) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-border pt-3 text-base font-semibold">
                        <dt class="text-fg">Jami</dt>
                        <dd class="tabular-nums text-fg">{{ formatPrice(registration.final_price) }}</dd>
                    </div>
                </dl>
            </Card>

            <Card v-if="onlinePaymentAvailable" title="Karta orqali to'lash" description="Payme yoki Click — to'lovdan so'ng ishtirokingiz avtomatik tasdiqlanadi.">
                <!-- the discount code was applied at registration and is already in the price -->
                <CheckoutForm product-type="olympiad_registration" :product-id="registration.id" :allow-promo="false" />
            </Card>

            <Card title="Coin bilan to'lash">
                <form id="pay-form" class="space-y-4" @submit.prevent="submit">
                    <dl class="divide-y divide-border rounded-md border border-border px-3 text-sm">
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Kerak</dt>
                            <dd class="flex items-center gap-1 font-semibold tabular-nums"><CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />{{ groupDigits(coinsNeeded) }} coin</dd>
                        </div>
                        <div class="flex justify-between py-2.5">
                            <dt class="text-fg-muted">Balansingiz</dt>
                            <dd class="font-medium tabular-nums">{{ groupDigits(userCoins) }} coin</dd>
                        </div>
                    </dl>
                    <p class="text-13 text-fg-subtle">1 coin = {{ groupDigits(coinsExchangeRate) }} so'm</p>
                    <Alert v-if="!enough" variant="warning">
                        Coin yetarli emas. Darslarni tugatib va testlardan o'tib coin to'plang.
                    </Alert>
                </form>
                <template #footer>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button variant="secondary" :href="route('student.olympiads.show', olympiad.slug)">Bekor qilish</Button>
                        <Button type="submit" form="pay-form" :loading="form.processing" :disabled="!enough">Coin bilan to'lash</Button>
                    </div>
                </template>
            </Card>
        </div>
    </StudentLayout>
</template>
