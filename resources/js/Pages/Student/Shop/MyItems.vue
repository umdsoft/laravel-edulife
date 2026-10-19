<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArchiveBoxIcon, ShoppingBagIcon, UserCircleIcon, StarIcon, SwatchIcon, BoltIcon, LightBulbIcon, HeartIcon, GiftIcon,
} from '@heroicons/vue/24/outline';
import { CircleStackIcon, CheckCircleIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, Tabs } from '@/Components/UI';

const props = defineProps({
    purchases: { type: Array, default: () => [] },
    balance: { type: Number, default: 0 },
});

const TYPE_ICONS = { avatar: UserCircleIcon, badge: StarIcon, theme: SwatchIcon, boost: BoltIcon, hint: LightBulbIcon, extra_life: HeartIcon };

const filter = ref('all');
const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: props.purchases.length || undefined },
    { key: 'wearable', label: 'Taqiladigan', count: props.purchases.filter((p) => p.equippable).length || undefined },
    { key: 'usable', label: 'Ishlatiladigan', count: props.purchases.filter((p) => !p.equippable).length || undefined },
]);
const shown = computed(() => props.purchases.filter((p) => filter.value === 'all'
    || (filter.value === 'wearable' ? p.equippable : !p.equippable)));

const pad = (n) => String(n).padStart(2, '0');
const date = (iso) => {
    if (!iso) return '';
    const d = new Date(iso);
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const busy = ref(null);
const toggle = (p) => {
    router.post(route('student.shop.equip', p.id), {}, {
        preserveScroll: true,
        onStart: () => (busy.value = p.id),
        onFinish: () => (busy.value = null),
    });
};
</script>

<template>
    <Head title="Mening buyumlarim" />

    <StudentLayout title="Mening buyumlarim">
        <PageHeader title="Mening buyumlarim" description="Do'kondan olgan buyumlaringiz: avatar, nishon va mavzularni taqing."
            :breadcrumbs="[{ label: 'Coin do\'kon', href: route('student.shop.index') }, { label: 'Mening buyumlarim' }]">
            <template #meta>
                <Badge variant="warning" :icon="CircleStackIcon"><span class="tabular-nums">{{ balance }}</span> coin</Badge>
            </template>
            <template #actions>
                <Button :icon-left="ShoppingBagIcon" :href="route('student.shop.index')">Do'konga o'tish</Button>
            </template>
        </PageHeader>

        <div class="space-y-5">
            <Tabs v-if="purchases.length" v-model="filter" :tabs="tabs" variant="pills" label="Buyumlar filtri" />

            <EmptyState v-if="!purchases.length" :icon="ArchiveBoxIcon" bordered size="lg" title="Hali buyum sotib olmagansiz"
                description="To'plagan coinlaringizga do'kondan avatar, nishon va foydali buyumlar oling.">
                <Button :href="route('student.shop.index')">Do'konni ochish</Button>
            </EmptyState>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="p in shown" :key="p.id" padding="sm" :class="p.is_equipped ? 'ring-1 ring-success-600' : ''">
                    <div class="flex h-full flex-col">
                        <div class="flex items-start gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                                <component :is="TYPE_ICONS[p.type] || GiftIcon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-semibold text-fg">{{ p.name }}</h3>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <Badge size="sm" variant="neutral">{{ p.type_label }}</Badge>
                                    <Badge v-if="p.is_equipped" size="sm" variant="success" :icon="CheckCircleIcon">Taqilgan</Badge>
                                    <Badge v-if="p.is_expired" size="sm" variant="danger">Muddati tugagan</Badge>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-2 flex-1 text-13 text-fg-muted">{{ p.description }}</p>
                        <dl class="mt-3 space-y-1 text-13 text-fg-subtle">
                            <div class="flex justify-between"><dt>Sotib olingan</dt><dd class="tabular-nums">{{ date(p.purchased_at) }}</dd></div>
                            <div v-if="p.remaining_uses !== null" class="flex justify-between"><dt>Qolgan</dt><dd class="tabular-nums">{{ p.remaining_uses }} marta</dd></div>
                            <div v-if="p.expires_at" class="flex justify-between"><dt>Amal qiladi</dt><dd class="tabular-nums">{{ date(p.expires_at) }} gacha</dd></div>
                        </dl>
                        <div v-if="p.equippable" class="mt-4 border-t border-border pt-4">
                            <Button block size="sm" :variant="p.is_equipped ? 'secondary' : 'primary'" :loading="busy === p.id" @click="toggle(p)">
                                {{ p.is_equipped ? 'Olib qo\'yish' : 'Taqish' }}
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>
