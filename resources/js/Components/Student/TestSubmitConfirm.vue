<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/20/solid';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    show: Boolean,
    unansweredCount: Number,
    // optional: disables the confirm button while submitting
    loading: Boolean,
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" size="sm" title="Testni topshirish"
        description="Topshirilgandan keyin javoblarni o'zgartirib bo'lmaydi." @close="$emit('close')">
        <div v-if="unansweredCount > 0"
            class="flex items-start gap-2.5 rounded-md border border-warning-100 bg-warning-50 p-3 text-sm text-warning">
            <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <span>Sizda <strong class="font-semibold tabular-nums">{{ unansweredCount }}</strong> ta javobsiz savol qoldi.</span>
        </div>
        <p v-else class="text-sm text-fg-muted">Barcha savollarga javob berdingiz. Testni topshirishga tayyormisiz?</p>

        <template #footer>
            <Button variant="secondary" @click="$emit('close')">Davom etish</Button>
            <Button :loading="loading" @click="$emit('confirm')">Ha, topshirish</Button>
        </template>
    </Modal>
</template>
