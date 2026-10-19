<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AcademicCapIcon, BeakerIcon, BookOpenIcon, CheckBadgeIcon, ChevronDownIcon, ClipboardDocumentCheckIcon, ClockIcon,
    FireIcon, FlagIcon, LightBulbIcon, ShoppingBagIcon, SparklesIcon, TicketIcon, TrophyIcon, UserCircleIcon, UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon, CircleStackIcon, GiftIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, Modal, PageHeader, StatCard } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    packages: {
        type: Array,
        default: () => []
    },
    balance: {
        type: Number,
        default: 0
    },
    level: {
        type: Number,
        default: 1
    }
});

const toast = useToast();
const selectedPackage = ref(null);
const processing = ref(false);
const showEarnGuide = ref(true);
const confirmPkg = ref(null);

const formatNumber = (num) => new Intl.NumberFormat('ru-RU').format(Number(num) || 0).replace(/ /g, ' ');
const formatPrice = formatNumber;
const totalCoins = (pkg) => Number(pkg.total_coins) || (Number(pkg.coins) || 0) + (Number(pkg.bonus_coins) || 0);
const perCoin = (pkg) => {
    const t = totalCoins(pkg);
    return t > 0 ? Math.round((Number(pkg.price) || 0) / t) : null;
};

const askPurchase = (pkg) => {
    if (processing.value) return;
    confirmPkg.value = pkg;
};

const purchase = (pkg) => {
    if (processing.value) return;
    // Only navigate to a checkout route that actually exists
    if (!route().has('student.payment.checkout')) {
        toast.info('Onlayn to\'lov hozircha ishga tushirilmagan. Tez orada mavjud bo\'ladi.');
        confirmPkg.value = null;
        return;
    }
    processing.value = true;
    selectedPackage.value = pkg.id;
    router.visit(route('student.payment.checkout', { package: pkg.id, type: 'coin' }), {
        onFinish: () => {
            processing.value = false;
            selectedPackage.value = null;
        },
    });
};

const popularId = computed(() => props.packages.find((p) => p.is_popular)?.id ?? null);

// Coin earning methods
const earnMethods = [
    { icon: BookOpenIcon, title: 'Dars tugatish', coins: '10–50', desc: 'Har bir darsni tugatganingizda' },
    { icon: ClipboardDocumentCheckIcon, title: 'Test topshirish', coins: '20–100', desc: 'Testlarni muvaffaqiyatli topshiring' },
    { icon: FireIcon, title: 'Kunlik seriya', coins: '5–50', desc: 'Har kuni platformaga kiring' },
    { icon: AcademicCapIcon, title: 'Kurs tugatish', coins: '100–500', desc: 'To\'liq kursni yakunlang' },
    { icon: FlagIcon, title: 'Haftalik maqsad', coins: '50–200', desc: 'Haftalik maqsadlarga erishing' },
    { icon: UserPlusIcon, title: 'Do\'st taklif qilish', coins: '100', desc: 'Yangi foydalanuvchi taklif qiling' },
    { icon: TrophyIcon, title: 'Turnir g\'olibi', coins: '200–1000', desc: 'Turnirlarda g\'olib bo\'ling' },
    { icon: SparklesIcon, title: 'Olimpiada', coins: '500–2000', desc: 'Olimpiadalarda qatnashing' },
];

// Coin spending methods
const spendMethods = [
    { icon: BookOpenIcon, title: 'Premium kurslar', desc: 'Eksklyuziv kurslarga kirish' },
    { icon: BeakerIcon, title: 'Laboratoriya', desc: 'Maxsus tajribalar ochish' },
    { icon: LightBulbIcon, title: 'Maslahatlar', desc: 'Test javoblariga maslahat' },
    { icon: ClockIcon, title: 'Qo\'shimcha vaqt', desc: 'Test uchun qo\'shimcha vaqt' },
    { icon: UserCircleIcon, title: 'Avatar va nishonlar', desc: 'Profilingizni bezating' },
    { icon: TicketIcon, title: 'Musobaqalar', desc: 'Maxsus musobaqalarga ro\'yxatdan o\'ting' },
];
</script>

<template>

    <Head title="Coin do'kon" />

    <StudentLayout title="Coin do'kon">
        <PageHeader title="Coin do'kon" description="Premium kurslar va maxsus imkoniyatlar uchun coin to'plami tanlang." />

        <div class="space-y-6">
            <!-- Balance -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Balansingiz" :value="balance" unit="coin" :icon="CircleStackIcon" tone="warning" />
                <StatCard label="Daraja" :value="level" :icon="SparklesIcon" tone="brand" />
            </div>

            <!-- Packages -->
            <EmptyState v-if="packages.length === 0" :icon="ShoppingBagIcon" bordered
                title="Coin paketlari mavjud emas" description="Yangi paketlar qo'shilganda shu yerda ko'rinadi." />

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="pkg in packages" :key="pkg.id" padding="sm"
                    :class="pkg.id === popularId ? 'ring-1 ring-brand-600' : ''">
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
                            {{ formatNumber(pkg.coins) }} <span class="text-sm font-normal text-fg-subtle">coin</span>
                        </p>
                        <div class="mt-2 min-h-[24px]">
                            <Badge v-if="pkg.bonus_coins > 0" variant="success" size="sm" :icon="GiftIcon">
                                <span class="tabular-nums">+{{ formatNumber(pkg.bonus_coins) }} bonus</span>
                            </Badge>
                        </div>
                        <p v-if="pkg.bonus_coins > 0" class="mt-1 text-xs tabular-nums text-fg-subtle">
                            Jami: {{ formatNumber(totalCoins(pkg)) }} coin
                        </p>

                        <div class="mt-4 flex-1 border-t border-border pt-4">
                            <p class="text-lg font-semibold tabular-nums text-fg">
                                {{ formatPrice(pkg.price) }} <span class="text-sm font-normal text-fg-subtle">so'm</span>
                            </p>
                            <p v-if="perCoin(pkg) !== null" class="mt-0.5 text-xs tabular-nums text-fg-subtle">
                                ≈ {{ formatNumber(perCoin(pkg)) }} so'm / coin
                            </p>
                        </div>

                        <Button class="mt-4" block :variant="pkg.id === popularId ? 'primary' : 'secondary'"
                            :loading="processing && selectedPackage === pkg.id" @click="askPurchase(pkg)">
                            Sotib olish
                        </Button>
                    </div>
                </Card>
            </div>

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
                            <h2 class="text-base font-semibold text-fg">Coinlarni bepul yig'ish yo'llari</h2>
                            <p class="text-13 text-fg-subtle">Sotib olmasdan coin to'plash imkoniyatlari</p>
                        </div>
                    </div>
                    <ChevronDownIcon :class="['h-5 w-5 shrink-0 text-fg-subtle transition-transform duration-150', showEarnGuide ? 'rotate-180' : '']"
                        aria-hidden="true" />
                </button>

                <div v-show="showEarnGuide" id="earn-guide" class="border-t border-border">
                    <div class="px-5 py-5 sm:px-6">
                        <h3 class="mb-3 text-sm font-semibold text-fg">Coin yig'ish usullari</h3>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div v-for="method in earnMethods" :key="method.title"
                                class="flex items-start gap-3 rounded-md border border-border p-3">
                                <component :is="method.icon" class="mt-0.5 h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="truncate text-sm font-medium text-fg">{{ method.title }}</h4>
                                        <span class="whitespace-nowrap text-xs font-medium tabular-nums text-warning">+{{ method.coins }}</span>
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-fg-subtle">{{ method.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border bg-surface-2/50 px-5 py-5 sm:px-6">
                        <h3 class="mb-3 text-sm font-semibold text-fg">Coinlarni nimaga sarflash mumkin?</h3>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="method in spendMethods" :key="method.title"
                                class="flex items-center gap-3 rounded-md border border-border bg-surface p-3">
                                <component :is="method.icon" class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                <div class="min-w-0">
                                    <h4 class="text-sm font-medium text-fg">{{ method.title }}</h4>
                                    <p class="text-xs text-fg-subtle">{{ method.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border px-5 py-5 sm:px-6">
                        <h3 class="mb-3 text-sm font-semibold text-fg">Foydali maslahatlar</h3>
                        <ul class="grid gap-2 text-sm text-fg-muted sm:grid-cols-2 lg:grid-cols-3">
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Har kuni kirib seriyangizni saqlang</li>
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Testlarni yuqori ball bilan topshiring</li>
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Do'stlaringizni taklif qiling</li>
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Turnirlarda qatnashing va g'olib bo'ling</li>
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Kurslarni to'liq tugatib sertifikat oling</li>
                            <li class="flex items-start gap-2"><CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />Haftalik maqsadlarga erishing</li>
                        </ul>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Confirm purchase -->
        <Modal :show="!!confirmPkg" size="sm" title="Xaridni tasdiqlang" @close="confirmPkg = null">
            <div v-if="confirmPkg" class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-warning-50 text-warning">
                        <CircleStackIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-fg">{{ confirmPkg.name }}</p>
                        <p class="text-13 tabular-nums text-fg-subtle">{{ formatNumber(totalCoins(confirmPkg)) }} coin</p>
                    </div>
                </div>
                <dl class="divide-y divide-border rounded-md border border-border px-3 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-fg-muted">Narxi</dt>
                        <dd class="font-medium tabular-nums">{{ formatPrice(confirmPkg.price) }} so'm</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-fg-muted">Xariddan keyingi balans</dt>
                        <dd class="font-medium tabular-nums">{{ formatNumber(balance + totalCoins(confirmPkg)) }} coin</dd>
                    </div>
                </dl>
                <p class="flex items-start gap-2 text-13 text-fg-subtle">
                    <CheckBadgeIcon class="h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                    To'lov sahifasiga yo'naltirilasiz. Coinlar to'lovdan so'ng hisobingizga tushadi.
                </p>
            </div>
            <template #footer>
                <Button variant="secondary" @click="confirmPkg = null">Bekor qilish</Button>
                <Button :loading="processing" @click="purchase(confirmPkg)">To'lovga o'tish</Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
