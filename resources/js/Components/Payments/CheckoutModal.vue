<script setup>
/**
 * CheckoutModal — CheckoutForm in a modal (course page, shop, subscription plans).
 *   <CheckoutModal :show="!!selected" product-type="coin_package" :product-id="selected?.id" :title="selected?.name" @close="selected = null" />
 */
import { Modal } from '@/Components/UI';
import CheckoutForm from './CheckoutForm.vue';

defineProps({
    show: { type: Boolean, default: false },
    productType: { type: String, required: true },
    productId: { type: String, default: null },
    title: { type: String, default: "Onlayn to'lov" },
    description: { type: String, default: null },
    allowPromo: { type: Boolean, default: true },
});

defineEmits(['close']);
</script>

<template>
    <Modal :show="show" size="md" :title="title" :description="description" @close="$emit('close')">
        <!-- re-mounted for each product so the quote is fresh -->
        <CheckoutForm v-if="show && productId" :key="`${productType}-${productId}`" :product-type="productType"
            :product-id="productId" :allow-promo="allowPromo" />
    </Modal>
</template>
