<template>
    <StudentLayout title="Yutuqlar">
        <Head title="Yutuqlar" />

        <PageHeader title="Yutuqlar" description="Topshiriqlarni bajarib yutuqlarni oching va mukofot oling." />

        <div class="space-y-6">
            <!-- Progress -->
            <Card padding="sm">
                <div class="flex items-center gap-4">
                    <ProgressRing :value="total_unlocked || 0" :max="total_achievements || 1" :size="64" :stroke="6"
                        tone="brand" label="Ochilgan yutuqlar" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-fg">Ochilgan yutuqlar</p>
                        <p class="text-2xl font-semibold tabular-nums text-fg">
                            {{ total_unlocked || 0 }}
                            <span class="text-base font-normal text-fg-subtle">/ {{ total_achievements || 0 }}</span>
                        </p>
                    </div>
                    <Badge v-if="claimableCount" variant="success" :icon="GiftIcon">
                        <span class="tabular-nums">{{ claimableCount }}</span> ta mukofot kutmoqda
                    </Badge>
                </div>
            </Card>

            <!-- Category filter -->
            <div v-if="categories.length > 2" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <Tabs v-model="activeCategory" :tabs="categoryTabs" variant="pills" label="Yutuq turkumlari" />
            </div>

            <!-- Grid -->
            <EmptyState v-if="filteredAchievements.length === 0" :icon="TrophyIcon" bordered
                title="Yutuqlar topilmadi" description="Bu turkumda hozircha yutuq yo'q." />

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="achievement in filteredAchievements" :key="achievement.id" padding="sm"
                    :class="[
                        !achievement.is_unlocked ? 'shadow-none' : '',
                        achievement.is_unlocked && !achievement.is_claimed ? 'ring-1 ring-success-600/30' : '',
                    ]">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start gap-3">
                            <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-2xl"
                                :class="chipClass(achievement)">
                                <LockClosedIcon v-if="!achievement.is_unlocked" class="h-5 w-5" aria-hidden="true" />
                                <span v-else-if="achievement.icon" aria-hidden="true">{{ achievement.icon }}</span>
                                <TrophyIcon v-else class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold" :class="achievement.is_unlocked ? 'text-fg' : 'text-fg-muted'">
                                    {{ achievement.title }}
                                </h3>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <Badge size="sm" :variant="rarityVariant(achievement.rarity)">{{ rarityLabel(achievement.rarity) }}</Badge>
                                    <span v-if="achievement.category" class="text-xs text-fg-subtle">{{ categoryLabel(achievement.category) }}</span>
                                </div>
                            </div>
                        </div>

                        <p v-if="achievement.description" class="mt-3 flex-1 text-13 text-fg-muted">{{ achievement.description }}</p>
                        <div v-else class="flex-1" />

                        <div class="mt-4 flex items-center justify-between gap-2 border-t border-border pt-3">
                            <div class="flex flex-wrap items-center gap-3 text-xs text-fg-muted">
                                <span v-if="achievement.xp_reward" class="flex items-center gap-1 tabular-nums">
                                    <SparklesIcon class="h-4 w-4 text-brand" aria-hidden="true" />{{ achievement.xp_reward }} XP
                                </span>
                                <span v-if="achievement.coin_reward" class="flex items-center gap-1 tabular-nums">
                                    <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />{{ achievement.coin_reward }} coin
                                </span>
                            </div>

                            <Button v-if="achievement.is_unlocked && !achievement.is_claimed" size="sm"
                                :loading="claimingId === achievement.id" @click="handleAchievementClick(achievement)">
                                Olish
                            </Button>
                            <Badge v-else-if="achievement.is_claimed" size="sm" variant="success" :icon="CheckIcon">Olingan</Badge>
                            <span v-else-if="achievement.is_unlocked" class="text-xs text-fg-subtle">Ochilgan</span>
                            <span v-else class="flex items-center gap-1 text-xs text-fg-subtle">
                                <LockClosedIcon class="h-3.5 w-3.5" aria-hidden="true" /> Qulflangan
                            </span>
                        </div>
                        <p v-if="achievement.is_unlocked && achievement.unlocked_at" class="mt-2 text-xs tabular-nums text-fg-subtle">
                            Ochilgan: {{ formatDate(achievement.unlocked_at) }}
                        </p>
                    </div>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { GiftIcon, LockClosedIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import { CheckIcon, CircleStackIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, ProgressRing, Tabs } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    achievements: Array,
    total_unlocked: Number,
    total_achievements: Number,
});

const toast = useToast();
const activeCategory = ref('all');
const claimingId = ref(null);

const categories = computed(() => {
    return ['all', ...new Set((props.achievements || []).map(a => a.category))];
});

const filteredAchievements = computed(() => {
    const list = props.achievements || [];
    if (activeCategory.value === 'all') return list;
    return list.filter(a => a.category === activeCategory.value);
});

const handleAchievementClick = (achievement) => {
    if (achievement.is_unlocked && !achievement.is_claimed) {
        if (claimingId.value) return;
        claimingId.value = achievement.id;
        axios.post(route('student.achievements.claim', achievement.id))
            .then((res) => {
                if (res.data?.success === false) toast.error(res.data?.message || 'Mukofotni olib bo\'lmadi');
                else toast.success('Mukofot olindi');
                router.reload();
            })
            .catch((e) => toast.error(e.response?.data?.message || 'Mukofotni olib bo\'lmadi'))
            .finally(() => { claimingId.value = null; });
    }
};

// Presentation
const CATEGORY = {
    all: 'Barchasi', learning: 'O\'qish', testing: 'Testlar', battle: 'Janglar', tournament: 'Turnirlar',
    streak: 'Seriya', social: 'Ijtimoiy', special: 'Maxsus', legendary: 'Afsonaviy', mechanics: 'Mexanika',
    electricity: 'Elektr', premium: 'Premium', items: 'Buyumlar', boosts: 'Kuchaytirgichlar',
};
const categoryLabel = (c) => CATEGORY[c] || c;
const categoryTabs = computed(() => categories.value.map((c) => ({
    key: c,
    label: categoryLabel(c),
    count: c === 'all' ? (props.achievements || []).length : (props.achievements || []).filter((a) => a.category === c).length,
})));

const RARITY = {
    common: { label: 'Oddiy', variant: 'neutral' },
    uncommon: { label: 'Kam uchraydigan', variant: 'success' },
    rare: { label: 'Noyob', variant: 'info' },
    epic: { label: 'Epik', variant: 'brand' },
    legendary: { label: 'Afsonaviy', variant: 'warning' },
};
const rarityLabel = (r) => RARITY[r]?.label || r || 'Oddiy';
const rarityVariant = (r) => RARITY[r]?.variant || 'neutral';

const chipClass = (a) => {
    if (!a.is_unlocked) return 'bg-surface text-fg-subtle ring-1 ring-inset ring-border';
    if (a.is_claimed) return 'bg-success-50 ring-1 ring-inset ring-success-100';
    return 'bg-brand-50 ring-1 ring-inset ring-brand-100';
};

const claimableCount = computed(() => (props.achievements || []).filter((a) => a.is_unlocked && !a.is_claimed).length);

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
</script>
