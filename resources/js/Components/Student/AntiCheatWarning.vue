<script setup>
import { computed } from 'vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';

const props = defineProps({
    show: Boolean,
    warningsCount: Number,
    maxWarnings: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['close']);
const used = computed(() => Math.min(props.warningsCount || 0, props.maxWarnings));
</script>

<template>
    <Modal :show="show" size="sm" @close="emit('close')">
        <!-- Modal (no title prop) labels itself by #modal-title -->
        <div class="p-6 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-danger-50 text-danger">
                <ExclamationTriangleIcon class="h-6 w-6" aria-hidden="true" />
            </div>

            <h2 id="modal-title" class="text-lg font-semibold text-fg">Test oynasidan chiqildi</h2>
            <p class="mt-2 text-sm text-fg-muted">
                Boshqa oyna yoki ilovaga o'tish test qoidalariga zid va qayd etiladi.
                Ogohlantirishlar limitga yetsa, test avtomatik yakunlanadi.
            </p>

            <div class="mx-auto mt-5 max-w-xs text-left">
                <div class="mb-1.5 flex justify-between text-13">
                    <span class="text-fg-muted">Ogohlantirishlar</span>
                    <span class="font-semibold text-danger tabular-nums">{{ warningsCount }} / {{ maxWarnings }}</span>
                </div>
                <ProgressBar :value="used" :max="maxWarnings" tone="danger" size="sm" />
            </div>

            <Button block class="mt-6" autofocus @click="emit('close')">Tushundim, davom etish</Button>
        </div>
    </Modal>
</template>
