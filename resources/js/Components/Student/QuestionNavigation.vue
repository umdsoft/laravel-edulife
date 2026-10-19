<script setup>
import { ChevronLeftIcon, ChevronRightIcon, CheckIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';

defineProps({
    hasPrevious: Boolean,
    hasNext: Boolean,
    isLast: Boolean,
    isSubmitting: Boolean,
});

defineEmits(['previous', 'next', 'submit']);
</script>

<template>
    <div class="flex items-center justify-between gap-3 border-t border-border pt-5">
        <Button v-if="hasPrevious" variant="secondary" :icon-left="ChevronLeftIcon" @click="$emit('previous')">
            Oldingi
        </Button>
        <span v-else />

        <Button v-if="hasNext" :icon-right="ChevronRightIcon" @click="$emit('next')">
            Keyingi
        </Button>
        <Button v-else-if="isLast" :loading="isSubmitting" :icon-left="CheckIcon" @click="$emit('submit')">
            Testni topshirish
        </Button>
    </div>
</template>
