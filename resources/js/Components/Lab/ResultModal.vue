<script setup>
import { computed } from 'vue';
import { ArrowPathIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import { CircleStackIcon, ClockIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import ProgressRing from '@/Components/UI/ProgressRing.vue';

const props = defineProps({
    result: Object,
    experiment: Object,
});

defineEmits(['close']);

const percentage = computed(() => Number(props.result?.percentage || 0));

const gradeTone = computed(() => {
    const p = percentage.value;
    if (p >= 90) return 'success';
    if (p >= 70) return 'brand';
    if (p >= 50) return 'warning';
    return 'danger';
});

const rarityVariant = (rarity) =>
    ({ common: 'neutral', rare: 'info', epic: 'brand', legendary: 'warning' })[String(rarity || '').toLowerCase()] || 'neutral';

const stats = computed(() => [
    { key: 'xp', label: 'XP', value: `+${props.result?.xp_earned ?? 0}`, icon: SparklesIcon, color: 'text-brand' },
    { key: 'coins', label: 'Coin', value: `+${props.result?.coins_earned ?? 0}`, icon: CircleStackIcon, color: 'text-warning' },
    { key: 'time', label: 'Vaqt', value: props.result?.time_spent ?? '—', icon: ClockIcon, color: 'text-fg-subtle' },
]);
</script>

<template>
    <Modal :show="true" size="md" @close="$emit('close')">
        <div class="px-5 pb-2 pt-6 text-center sm:px-6">
            <div :class="[
                'mx-auto flex h-12 w-12 items-center justify-center rounded-full',
                result.passed ? 'bg-success-50 text-success' : 'bg-warning-50 text-warning',
            ]">
                <component :is="result.passed ? TrophyIcon : ArrowPathIcon" class="h-6 w-6" aria-hidden="true" />
            </div>
            <h2 id="modal-title" class="mt-3 text-xl font-semibold text-fg">
                {{ result.passed ? 'Tabriklaymiz!' : 'Yaxshi harakat!' }}
            </h2>
            <p class="mt-1 text-sm text-fg-muted">{{ experiment.title }}</p>

            <div class="mt-5 flex justify-center">
                <ProgressRing :value="percentage" :size="128" :stroke="10" :tone="gradeTone" label="Tajriba natijasi">
                    <span class="flex flex-col items-center leading-none">
                        <span class="text-3xl font-semibold text-fg tabular-nums">{{ percentage }}%</span>
                        <span v-if="result.grade" class="mt-1.5 text-sm font-medium text-fg-muted">{{ result.grade }}</span>
                    </span>
                </ProgressRing>
            </div>
            <p class="mt-3 text-13 text-fg-subtle">
                {{ result.passed ? "Tajriba muvaffaqiyatli yakunlandi." : "Natijani yaxshilash uchun tajribani qayta bajarib ko'ring." }}
            </p>
        </div>

        <dl class="grid grid-cols-3 gap-2 px-5 py-4 sm:px-6">
            <div v-for="s in stats" :key="s.key" class="rounded-md bg-surface-2 px-2 py-3 text-center">
                <dt class="flex items-center justify-center gap-1 text-xs text-fg-subtle">
                    <component :is="s.icon" :class="['h-3.5 w-3.5', s.color]" aria-hidden="true" />
                    {{ s.label }}
                </dt>
                <dd class="mt-1 text-base font-semibold text-fg tabular-nums">{{ s.value }}</dd>
            </div>
        </dl>

        <div v-if="result.badges_earned?.length" class="border-t border-border px-5 py-4 sm:px-6">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-fg-subtle">Yangi nishonlar</h3>
            <ul class="mt-3 space-y-2">
                <li v-for="badge in result.badges_earned" :key="badge.id"
                    class="flex items-center gap-3 rounded-md border border-border px-3 py-2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-warning-50 text-lg"
                        aria-hidden="true">
                        <template v-if="badge.icon">{{ badge.icon }}</template>
                        <TrophyIcon v-else class="h-5 w-5 text-warning" />
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-fg">{{ badge.name }}</span>
                    <Badge v-if="badge.rarity" size="sm" :variant="rarityVariant(badge.rarity)">{{ badge.rarity }}</Badge>
                </li>
            </ul>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-border bg-surface-2/50 px-5 py-4 sm:flex-row sm:px-6">
            <Button variant="secondary" class="sm:flex-1" :href="route('student.lab.show', experiment.slug)">
                Tajriba sahifasiga
            </Button>
            <Button class="sm:flex-1" :href="route('student.lab.index')">
                Laboratoriyaga qaytish
            </Button>
        </div>
    </Modal>
</template>
