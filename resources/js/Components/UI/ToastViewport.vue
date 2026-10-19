<script setup>
/**
 * ToastViewport — renders the global toast stack (useToast store). Mounted once by each layout.
 * Bottom-center on mobile (above the student tab bar), top-right on desktop.
 */
import { useToast } from '@/Composables/useToast';
import Toast from './Toast.vue';

const { toasts, dismiss } = useToast();
</script>

<template>
    <Teleport to="body">
        <div aria-live="polite"
            class="pointer-events-none fixed inset-x-0 bottom-20 z-toast flex flex-col items-center gap-2 px-4 sm:bottom-auto sm:left-auto sm:right-0 sm:top-4 sm:items-end sm:px-6 md:bottom-auto">
            <Toast v-for="t in toasts" :key="t.id" inline :type="t.type" :message="t.message" :title="t.title"
                :duration="t.duration" @close="dismiss(t.id)" />
        </div>
    </Teleport>
</template>
