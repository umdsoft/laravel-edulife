<script setup>
import { useForm } from '@inertiajs/vue3';
import { ClockIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import Textarea from '@/Components/UI/Textarea.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { formatClock } from './format.js';

const props = defineProps({
    lessonId: [String, Number],
    currentTime: Number,
    note: Object, // If editing
});

const emit = defineEmits(['close', 'saved']);

const colors = ['yellow', 'blue', 'green', 'pink'];
const colorMeta = {
    yellow: { label: 'Sariq', swatch: 'bg-warning-600' },
    blue: { label: "Ko'k", swatch: 'bg-info-600' },
    green: { label: 'Yashil', swatch: 'bg-success-600' },
    pink: { label: 'Qizil', swatch: 'bg-danger-600' },
};

const form = useForm({
    content: props.note?.content || '',
    video_timestamp: props.note?.video_timestamp || Math.floor(props.currentTime || 0),
    color: props.note?.color || 'yellow',
});

const submit = () => {
    if (props.note) {
        form.put(route('student.learn.notes.update', props.note.id), {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        form.post(route('student.learn.notes.store', props.lessonId), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('saved');
            },
        });
    }
};
</script>

<template>
    <form class="rounded-lg border border-border bg-surface p-4 shadow-xs" @submit.prevent="submit">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-7 items-center gap-1 rounded-full bg-surface-2 px-2.5 text-xs font-medium text-fg-muted tabular-nums">
                    <ClockIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ formatClock(form.video_timestamp) }}
                </span>
                <div role="radiogroup" aria-label="Qayd rangi" class="flex items-center gap-1.5">
                    <button v-for="color in colors" :key="color" type="button" role="radio"
                        :aria-checked="form.color === color" :aria-label="colorMeta[color].label" :class="[
                            'focus-ring h-6 w-6 rounded-full p-0.5 ring-offset-2 ring-offset-surface transition-shadow',
                            form.color === color ? 'ring-2 ring-border-strong' : '',
                        ]" @click="form.color = color">
                        <span :class="['block h-full w-full rounded-full', colorMeta[color].swatch]" />
                    </button>
                </div>
            </div>
            <IconButton :icon="XMarkIcon" size="sm" label="Yopish" @click="emit('close')" />
        </div>

        <Textarea v-model="form.content" :rows="3" autoresize placeholder="Muhim fikrni yozib qo'ying…"
            :error="form.errors.content" aria-label="Qayd matni" />

        <div class="mt-3 flex justify-end gap-2">
            <Button variant="ghost" size="sm" @click="emit('close')">Bekor qilish</Button>
            <Button type="submit" size="sm" :loading="form.processing" :disabled="!form.content.trim()">Saqlash</Button>
        </div>
    </form>
</template>
