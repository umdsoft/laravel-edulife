<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlayIcon, PlusIcon, ShoppingCartIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import CheckoutModal from '@/Components/Payments/CheckoutModal.vue';

const props = defineProps({
    course: Object,
    isEnrolled: Boolean,
});

const loading = ref(false);
const showCheckout = ref(false);

const enroll = () => {
    if (props.isEnrolled) {
        router.visit(route('student.courses.show', props.course.slug));
        return;
    }

    // paid course: pay online with Payme / Click (price is computed on the server)
    if (!props.course.is_free) {
        showCheckout.value = true;
        return;
    }

    loading.value = true;
    router.post(route('student.courses.enroll', props.course.id), {}, {
        onFinish: () => loading.value = false,
    });
};
</script>

<template>
    <Button size="lg" block :loading="loading" :icon-left="isEnrolled ? PlayIcon : (course.is_free ? PlusIcon : ShoppingCartIcon)"
        @click="enroll">
        <template v-if="loading">Yuklanmoqda…</template>
        <template v-else>{{ isEnrolled ? 'Davom ettirish' : (course.is_free ? 'Bepul yozilish' : 'Sotib olish') }}</template>
    </Button>

    <CheckoutModal v-if="!course.is_free && !isEnrolled" :show="showCheckout" product-type="course" :product-id="course.id"
        title="Kursni sotib olish" :description="course.title" @close="showCheckout = false" />
</template>
