<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ClockIcon, PencilSquareIcon, TrashIcon, BookmarkIcon } from '@heroicons/vue/20/solid';
import { BookmarkIcon as BookmarkOutline } from '@heroicons/vue/24/outline';
import IconButton from '@/Components/UI/IconButton.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    note: Object,
});

const emit = defineEmits(['edit', 'seek']);

const confirmOpen = ref(false);
const deleting = ref(false);

const deleteNote = () => {
    deleting.value = true;
    router.delete(route('student.learn.notes.destroy', props.note.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            confirmOpen.value = false;
        },
    });
};

const togglePin = () => {
    router.patch(route('student.learn.notes.toggle-pin', props.note.id), {}, {
        preserveScroll: true,
    });
};

// Colour is a quiet accent stripe, not a full tinted card
const stripe = {
    yellow: 'bg-warning-600',
    blue: 'bg-info-600',
    green: 'bg-success-600',
    pink: 'bg-danger-600',
};
</script>

<template>
    <article class="relative overflow-hidden rounded-lg border border-border bg-surface p-4 pl-5 shadow-xs">
        <span :class="['absolute inset-y-0 left-0 w-1', stripe[note.color] || stripe.yellow]" aria-hidden="true" />

        <div class="mb-2 flex items-center justify-between gap-2">
            <button type="button" @click="emit('seek', note.video_timestamp)"
                class="focus-ring inline-flex h-7 items-center gap-1 rounded-full bg-surface-2 px-2.5 text-xs font-medium text-fg-muted tabular-nums transition-colors hover:bg-brand-50 hover:text-brand"
                :aria-label="`Videoni ${note.formatted_timestamp} ga o'tkazish`">
                <ClockIcon class="h-3.5 w-3.5" aria-hidden="true" />
                {{ note.formatted_timestamp }}
            </button>

            <div class="flex items-center gap-0.5">
                <IconButton :icon="note.is_pinned ? BookmarkIcon : BookmarkOutline" size="sm"
                    :label="note.is_pinned ? 'Qadashni bekor qilish' : 'Qadash'"
                    :class="note.is_pinned ? '!text-brand' : ''" @click="togglePin" />
                <IconButton :icon="PencilSquareIcon" size="sm" label="Tahrirlash" @click="emit('edit', note)" />
                <IconButton :icon="TrashIcon" size="sm" label="O'chirish" @click="confirmOpen = true" />
            </div>
        </div>

        <p class="whitespace-pre-wrap text-sm leading-relaxed text-fg">{{ note.content }}</p>

        <Modal :show="confirmOpen" size="sm" title="Qaydni o'chirish" description="Bu amalni ortga qaytarib bo'lmaydi."
            @close="confirmOpen = false">
            <p class="line-clamp-3 rounded-md bg-surface-2 p-3 text-sm text-fg-muted">{{ note.content }}</p>
            <template #footer>
                <Button variant="secondary" @click="confirmOpen = false">Bekor qilish</Button>
                <Button variant="danger" :loading="deleting" @click="deleteNote">O'chirish</Button>
            </template>
        </Modal>
    </article>
</template>
