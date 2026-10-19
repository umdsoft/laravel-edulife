<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlayIcon, PlusIcon, ShoppingCartIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    course: Object,
    isEnrolled: Boolean,
});

const loading = ref(false);

const enroll = () => {
    if (props.isEnrolled) {
        router.visit(route('student.courses.show', props.course.slug));
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
</template>
