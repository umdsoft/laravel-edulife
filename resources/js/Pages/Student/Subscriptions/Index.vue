<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/20/solid';
import { RocketLaunchIcon, CreditCardIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';
import CheckoutModal from '@/Components/Payments/CheckoutModal.vue';

const props = defineProps({
    plans: { type: Array, default: () => [] },
    current: { type: Object, default: null },
    paymentProviders: { type: Array, default: () => [] },
});

const selected = ref(null);
const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
</script>

<template>
    <StudentLayout title="Obuna tariflari">
        <Head title="Obuna tariflari" />

        <PageHeader title="Obuna tariflari" description="Tarifni tanlang va Payme yoki Click orqali to'lang."
            :breadcrumbs="[{ label: 'Coin do\'kon', href: route('student.shop.index') }, { label: 'Obuna tariflari' }]" />

        <div class="space-y-6">
            <Alert v-if="current" variant="success" :title="`Faol tarif: ${current.plan}`">
                <template v-if="current.ends_at">{{ current.ends_at }} gacha amal qiladi.</template>
                Xuddi shu tarifni yana to'lasangiz, muddat davom ettiriladi; boshqa tarif darhol joriy tarif o'rnini oladi.
            </Alert>
            <Alert v-if="plans.length && !paymentProviders.length" variant="warning" title="To'lov tizimlari sozlanmagan">
                Tarifni sotib olish uchun administrator bilan bog'laning.
            </Alert>

            <EmptyState v-if="!plans.length" :icon="RocketLaunchIcon" bordered title="Hozircha tariflar yo'q"
                description="Administrator tariflarni qo'shganda shu yerda ko'rinadi." />

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="plan in plans" :key="plan.id" :class="plan.is_featured ? 'ring-1 ring-brand-600' : ''">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="text-base font-semibold text-fg">{{ plan.name }}</h2>
                            <Badge v-if="plan.is_current" variant="success" size="sm" dot>Faol</Badge>
                            <Badge v-else-if="plan.is_featured" variant="brand" size="sm">Tavsiya etiladi</Badge>
                        </div>
                        <p v-if="plan.description" class="mt-1 text-13 text-fg-muted">{{ plan.description }}</p>

                        <p class="mt-4 text-2xl font-semibold tabular-nums text-fg">
                            {{ groupDigits(plan.price) }} <span class="text-sm font-normal text-fg-subtle">so'm / {{ plan.period }}</span>
                        </p>
                        <p v-if="plan.original_price && plan.original_price > plan.price" class="text-13 tabular-nums text-fg-subtle line-through">
                            {{ groupDigits(plan.original_price) }} so'm
                        </p>

                        <ul v-if="plan.features.length" class="mt-4 flex-1 space-y-2 border-t border-border pt-4 text-sm">
                            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-fg-muted">
                                <CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />{{ feature }}
                            </li>
                        </ul>
                        <div v-else class="flex-1" />

                        <Button class="mt-5" block :variant="plan.is_featured ? 'primary' : 'secondary'" :icon-left="CreditCardIcon"
                            :disabled="!paymentProviders.length" @click="selected = plan">
                            {{ plan.is_current ? 'Muddatni uzaytirish' : 'Sotib olish' }}
                        </Button>
                    </div>
                </Card>
            </div>
        </div>

        <CheckoutModal :show="!!selected" product-type="subscription" :product-id="selected?.id"
            :title="selected ? `«${selected.name}» tarifi` : 'Tarif'" :description="selected ? `Muddat: ${selected.period}` : null"
            @close="selected = null" />
    </StudentLayout>
</template>
