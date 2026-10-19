<script setup>
import { VariableIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Modal from '@/Components/UI/Modal.vue';

defineProps({
    formulas: Array,
});

defineEmits(['close']);
</script>

<template>
    <Modal :show="true" title="Formulalar" description="Ushbu tajribada qo'llaniladigan formulalar va kattaliklar"
        size="3xl" @close="$emit('close')">
        <div v-if="formulas?.length" class="grid gap-4 pb-3 sm:grid-cols-2">
            <article v-for="formula in formulas" :key="formula.id" class="rounded-lg border border-border bg-surface p-4">
                <h3 class="text-sm font-semibold text-fg">{{ formula.name }}</h3>
                <div class="my-3 rounded-md bg-surface-2 px-3 py-3 text-center font-serif text-xl text-fg">
                    {{ formula.latex }}
                </div>
                <p v-if="formula.description" class="text-13 leading-5 text-fg-muted">{{ formula.description }}</p>

                <dl v-if="formula.variables?.length" class="mt-3 divide-y divide-border border-t border-border text-13">
                    <div v-for="v in formula.variables" :key="v.symbol" class="grid grid-cols-[2.5rem_1fr_auto] items-baseline gap-2 py-1.5">
                        <dt class="font-serif font-semibold text-brand">{{ v.symbol }}</dt>
                        <dd class="text-fg-muted">{{ v.name }}</dd>
                        <dd v-if="v.unit" class="text-fg-subtle">[{{ v.unit }}]</dd>
                    </div>
                </dl>
            </article>
        </div>
        <EmptyState v-else :icon="VariableIcon" title="Formulalar yo'q" description="Bu tajriba uchun formulalar qo'shilmagan." />
    </Modal>
</template>
