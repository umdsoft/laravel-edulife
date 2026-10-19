<script setup>
defineProps({
    question: Object,
    // option ids are UUID strings in the backend; numbers kept for backward compatibility
    selectedOptionId: [Number, String],
    disabled: Boolean,
});

const emit = defineEmits(['select']);

const letter = (i) => String.fromCharCode(65 + i);
</script>

<template>
    <div class="mx-auto w-full max-w-2xl">
        <div class="mb-4 rounded-lg border border-border bg-surface p-6 text-center shadow-xs sm:p-8">
            <p class="text-lg font-semibold leading-relaxed text-fg sm:text-xl">{{ question.content }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2" role="group" aria-label="Javob variantlari">
            <button v-for="(option, i) in question.options" :key="option.id" type="button"
                @click="$emit('select', option.id)" :disabled="disabled"
                :aria-pressed="selectedOptionId === option.id"
                class="focus-ring group flex min-h-[56px] w-full items-center gap-3 rounded-md border px-4 py-3 text-left transition-colors duration-150 ease-out disabled:cursor-not-allowed"
                :class="[
                    selectedOptionId === option.id
                        ? 'border-brand-600 bg-brand-50 text-fg ring-1 ring-brand-600'
                        : 'border-border-strong bg-surface text-fg hover:border-brand-200 hover:bg-surface-2',
                    disabled && selectedOptionId !== option.id ? 'opacity-60' : '',
                ]">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-sm text-13 font-semibold"
                    :class="selectedOptionId === option.id ? 'bg-brand-600 text-white' : 'bg-surface-2 text-fg-muted group-hover:text-fg'"
                    aria-hidden="true">
                    {{ letter(i) }}
                </span>
                <span class="text-sm font-medium">{{ option.content }}</span>
            </button>
        </div>
    </div>
</template>
