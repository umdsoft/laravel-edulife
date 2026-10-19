<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AcademicCapIcon, BookOpenIcon, ChevronDownIcon, ClipboardDocumentCheckIcon, FireIcon, FlagIcon, LightBulbIcon,
    ShoppingBagIcon, SparklesIcon, TrophyIcon, UserCircleIcon, StarIcon, BoltIcon, HeartIcon, SwatchIcon, ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';
import { CircleStackIcon, GiftIcon, LockClosedIcon, CheckIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Badge, Button, Card, EmptyState, Modal, PageHeader, StatCard } from '@/Components/UI';

const props = defineProps({
    packages: { type: Array, default: () => [] },
    // bought with earned coins
    items: { type: Array, default: () => [] },
    balance: { type: Number, default: 0 },
    level: { type: Number, default: 1 },
    onlinePaymentAvailable: { type: Boolean, default: false },
});

const formatNumber = (num) => new Intl.NumberFormat('ru-RU').format(Number(num) || 0).replace(/\s/g, ' ');
const totalCoins = (pkg) => Number(pkg.total_coins) || (Number(pkg.coins) || 0) + (Number(pkg.bonus_coins) || 0);

const TYPE_ICONS = { avatar: UserCircleIcon, badge: StarIcon, theme: SwatchIcon, boost: BoltIcon, hint: LightBulbIcon, extra_life: HeartIcon };

const itemState = (item) => {
    if (item.limit_reached) return { disabled: true, label: 'Sotib olingan' };
    if (props.level < item.min_level) return { disabled: true, label: `${item.min_level}-darajadan` };
    if (props.balance < item.price) return { disabled: true, label: 'Coin yetarli emas' };
    return { disabled: false, label: 'Sotib olish' };
};

const confirmItem = ref(null);
const buying = ref(false);
const buy = () => {
    const item = confirmItem.value;
    if (!item) return;
    router.post(route('student.shop.items.buy', item.id), {}, {
        preserveScroll: true,
        onStart: () => (buying.value = true),
        onFinish: () => {
            buying.value = false;
            confirmItem.value = null;
        },
    });
};
const balanceAfter = computed(() => (confirmItem.value ? props.balance - confirmItem.value.price : props.balance));

const popularId = computed(() => props.packages.find((p) => p.is_popular)?.id ?? null);
const showEarnGuide = ref(false);

const earnMethods = [
    { icon: BookOpenIcon, title: 'Dars tugatish', desc: 'Har bir darsni tugatganingizda' },
    { icon: ClipboardDocumentCheckIcon, title: 'Test topshirish', desc: 'Testlarni muvaffaqiyatli topshiring' },
    { icon: FireIcon, title: 'Kunlik seriya', desc: 'Har kuni platformaga kiring' },
    { icon: AcademicCapIcon, title: 'Kurs tugatish', desc: "To'liq kursni yakunlang" },
    { icon: FlagIcon, title: 'Kunlik vazifalar', desc: 'Kunlik vazifalarni bajaring' },
    { icon: TrophyIcon, title: 'Olimpiada', desc: 'Olimpiadalarda qatnashing' },
];
</script>

<template>
    <Head title="Coin do'kon" />

    <StudentLayout title="Coin do'kon">
        <PageHeader title="Coin do'kon" description="To'plagan coinlaringizni avatar, nishon va foydali buyumlarga almashtiring.">
            <template #actions>
                <Button variant="secondary" :icon-left="ArchiveBoxIcon" :href="route('student.shop.myItems')">Mening buyumlarim</Button>
            </template>
        </PageHeader>

        <div class="space-y-8">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Balansingiz" :value="balance" unit="coin" :icon="CircleStackIcon" tone="warning" />
                <StatCard label="Daraja" :value="level" :icon="SparklesIcon" tone="brand" />
            </div>

            <!-- Items (bought with coins) -->
            <section aria-labelledby="items-title" class="space-y-4">
                <h2 id="items-title" class="text-lg font-semibold text-fg">Buyumlar</h2>
                <EmptyState v-if="!items.length" :icon="ShoppingBagIcon" bordered title="Hozircha buyumlar yo'q"
                    description="Yangi buyumlar qo'shilganda shu yerda ko'rinadi." />
                <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="item in items" :key="item.id" padding="sm">
                        <div class="flex h-full flex-col">
                            <div class="flex items-start gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-brand-50 text-brand">
                                <img v-if="item.image_url" :src="item.image_url" alt="" class="h-full w-full object-cover" aria-hidden="true" />
                                <component :is="TYPE_ICONS[item.type] || GiftIcon" v-else class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-semibold text-fg">{{ item.name }}</h3>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <Badge size="sm" variant="neutral">{{ item.type_label }}</Badge>
                                        <Badge v-if="item.owned" size="sm" variant="success" :icon="CheckIcon">Sizda bor{{ item.owned > 1 ? ` ×${item.owned}` : '' }}</Badge>
                                        <Badge v-if="item.min_level > 1" size="sm" :variant="level >= item.min_level ? 'neutral' : 'warning'" :icon="level >= item.min_level ? null : LockClosedIcon">
                                            {{ item.min_level }}-daraja
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 line-clamp-2 flex-1 text-13 text-fg-muted">{{ item.description }}</p>
                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-border pt-4">
                                <p class="text-lg font-semibold tabular-nums text-fg">
                                    {{ formatNumber(item.price) }} <span class="text-sm font-normal text-fg-subtle">coin</span>
                                </p>
                                <Button size="sm" :variant="itemState(item).disabled ? 'secondary' : 'primary'" :disabled="itemState(item).disabled"
                                    @click="confirmItem = item">{{ itemState(item).label }}</Button>
                            </div>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- Coin packages (money) -->
            <section v-if="packages.length" aria-labelledby="packages-title" class="space-y-4">
                <h2 id="packages-title" class="text-lg font-semibold text-fg">Coin paketlari</h2>
                <Alert v-if="!onlinePaymentAvailable" variant="info" title="Onlayn to'lov hozircha ulanmagan">
                    Coin paketlarini pulga sotib olish tez orada ishga tushadi. Hozircha coinlarni darslar, testlar va olimpiadalar orqali to'plang.
                </Alert>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card v-for="pkg in packages" :key="pkg.id" padding="sm" :class="pkg.id === popularId ? 'ring-1 ring-brand-600' : ''">
                        <div class="flex h-full flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-warning-50 text-warning">
                                    <CircleStackIcon class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <Badge v-if="pkg.is_popular" variant="brand" size="sm">Eng ommabop</Badge>
                                <Badge v-else-if="pkg.badge" variant="warning" size="sm">{{ pkg.badge }}</Badge>
                            </div>
                            <h3 class="mt-3 text-sm font-medium text-fg-muted">{{ pkg.name }}</h3>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-fg">
                                {{ formatNumber(totalCoins(pkg)) }} <span class="text-sm font-normal text-fg-subtle">coin</span>
                            </p>
                            <div class="mt-2 min-h-[24px]">
                                <Badge v-if="pkg.bonus_coins > 0" variant="success" size="sm" :icon="GiftIcon">
                                    <span class="tabular-nums">+{{ formatNumber(pkg.bonus_coins) }} bonus</span>
                                </Badge>
                            </div>
                            <p class="mt-4 flex-1 border-t border-border pt-4 text-lg font-semibold tabular-nums text-fg">
                                {{ formatNumber(pkg.price) }} <span class="text-sm font-normal text-fg-subtle">so'm</span>
                            </p>
                            <Button class="mt-4" block variant="secondary" disabled>Tez orada</Button>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- Earn guide -->
            <Card padding="none">
                <button type="button" :aria-expanded="showEarnGuide" aria-controls="earn-guide"
                    class="focus-ring flex w-full items-center justify-between gap-4 rounded-lg px-5 py-4 text-left transition-colors duration-150 hover:bg-surface-2 sm:px-6"
                    @click="showEarnGuide = !showEarnGuide">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                            <LightBulbIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-fg">Coinlarni qanday yig'ish mumkin?</h2>
                            <p class="text-13 text-fg-subtle">O'qish orqali coin to'plash yo'llari</p>
                        </div>
                    </div>
                    <ChevronDownIcon :class="['h-5 w-5 shrink-0 text-fg-subtle transition-transform duration-150', showEarnGuide ? 'rotate-180' : '']" aria-hidden="true" />
                </button>
                <div v-show="showEarnGuide" id="earn-guide" class="border-t border-border px-5 py-5 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="method in earnMethods" :key="method.title" class="flex items-start gap-3 rounded-md border border-border p-3">
                            <component :is="method.icon" class="mt-0.5 h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <div class="min-w-0">
                                <h4 class="text-sm font-medium text-fg">{{ method.title }}</h4>
                                <p class="mt-0.5 text-xs text-fg-subtle">{{ method.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <Modal :show="!!confirmItem" size="sm" title="Xaridni tasdiqlang" @close="confirmItem = null">
            <div v-if="confirmItem" class="space-y-4">
                <p class="text-sm font-medium text-fg">{{ confirmItem.name }}</p>
                <dl class="divide-y divide-border rounded-md border border-border px-3 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-fg-muted">Narxi</dt>
                        <dd class="font-medium tabular-nums">{{ formatNumber(confirmItem.price) }} coin</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-fg-muted">Xariddan keyingi balans</dt>
                        <dd class="font-medium tabular-nums">{{ formatNumber(balanceAfter) }} coin</dd>
                    </div>
                </dl>
                <p class="text-13 text-fg-subtle">Coinlar hisobingizdan darhol yechiladi.</p>
            </div>
            <template #footer>
                <Button variant="secondary" @click="confirmItem = null">Bekor qilish</Button>
                <Button :loading="buying" @click="buy">Sotib olish</Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
