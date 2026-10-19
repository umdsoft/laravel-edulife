<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge, Button, Card, Input, PageHeader, Tabs } from '@/Components/UI';
import { CheckIcon, SparklesIcon, CircleStackIcon, BoltIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('xp');
const saving = ref(false);

const tabs = [
    { key: 'xp', label: 'XP', icon: SparklesIcon },
    { key: 'coin', label: 'Coin', icon: CircleStackIcon },
    { key: 'battle', label: 'Battle', icon: BoltIcon },
];

// Field definitions (key, label, unit, default). Keys are the settings table keys (SettingSeeder);
// defaults apply only when a setting is missing.
const FIELDS = {
    xp: [
        { key: 'xp_per_lesson', label: 'Dars uchun', unit: 'XP', default: 30 },
        { key: 'xp_per_test_pass', label: 'Test uchun', unit: 'XP', default: 100 },
        { key: 'xp_per_battle_win', label: "Battle g'alabasi uchun", unit: 'XP', default: 150 },
        { key: 'xp_multiplier_premium', label: "Premium ko'paytiruvchi", unit: '×', default: 1.5, step: '0.1' },
    ],
    coin: [
        { key: 'coin_per_lesson', label: 'Dars uchun', unit: 'coin', default: 5 },
        { key: 'coin_per_test_pass', label: 'Test uchun', unit: 'coin', default: 25 },
        { key: 'coin_per_battle_win', label: "Battle g'alabasi uchun", unit: 'coin', default: 35 },
        { key: 'coin_transfer_fee_percent', label: "O'tkazma komissiyasi", unit: '%', default: 5 },
    ],
    coinLimits: [
        { key: 'coin_monthly_limit_trial', label: 'Trial', unit: 'coin', default: 1500 },
        { key: 'coin_monthly_limit_standart', label: 'Standart', unit: 'coin', default: 2235 },
        { key: 'coin_monthly_limit_premium', label: 'Premium', unit: 'coin', default: 4980 },
        { key: 'coin_monthly_limit_vip', label: 'VIP', unit: 'coin', default: 9975 },
    ],
    battle: [
        { key: 'battle_questions_count', label: 'Bir o\'yindagi savollar', unit: 'ta', default: 5 },
        { key: 'battle_time_per_question', label: 'Har bir savolga vaqt', unit: 'soniya', default: 20 },
        { key: 'battle_daily_limit_trial', label: 'Kunlik limit (Trial)', unit: "o'yin", default: 3 },
        { key: 'elo_k_factor', label: 'ELO K koeffitsienti', unit: 'K', default: 32 },
    ],
};
const groupOf = (section) => (section === 'coinLimits' ? 'coin' : section);
const valueOf = (group, key, fallback) => props.settings?.[group]?.[key]?.value ?? fallback;
const hintOf = (group, key) => props.settings?.[group]?.[key]?.description || null;

const build = () => {
    const out = { xp: {}, coin: {}, battle: {} };
    Object.entries(FIELDS).forEach(([section, fields]) => {
        const group = groupOf(section);
        fields.forEach((f) => (out[group][f.key] = valueOf(group, f.key, f.default)));
    });
    return out;
};

// Local editable settings
const localSettings = reactive(build());
const snapshot = ref(JSON.stringify(localSettings));
const dirty = computed(() => JSON.stringify(localSettings) !== snapshot.value);

const resetChanges = () => {
    const original = JSON.parse(snapshot.value);
    Object.keys(original).forEach((g) => Object.assign(localSettings[g], original[g]));
};

const saveSettings = () => {
    saving.value = true;

    // Flatten settings for backend
    const settingsArray = [];
    Object.keys(localSettings).forEach((group) => {
        Object.keys(localSettings[group]).forEach((key) => {
            settingsArray.push({
                key: `${group}.${key}`,
                value: localSettings[group][key],
            });
        });
    });

    router.put(route('admin.settings.update'), {
        settings: settingsArray,
    }, {
        preserveScroll: true,
        onSuccess: () => (snapshot.value = JSON.stringify(localSettings)),
        onFinish: () => {
            saving.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Sozlamalar" />

        <PageHeader title="Tizim sozlamalari" description="Mukofotlar, limitlar va battle parametrlari">
            <template #meta>
                <Badge v-if="dirty" variant="warning" dot>Saqlanmagan o'zgarishlar</Badge>
            </template>
            <template #actions>
                <Button v-if="dirty" variant="ghost" :icon-left="ArrowUturnLeftIcon" :disabled="saving" @click="resetChanges">
                    Bekor qilish
                </Button>
                <Button :icon-left="CheckIcon" :loading="saving" :disabled="!dirty" @click="saveSettings">Saqlash</Button>
            </template>
        </PageHeader>

        <form class="max-w-3xl" @submit.prevent="saveSettings">
            <Tabs v-model="activeTab" :tabs="tabs" label="Sozlama bo'limlari">
                <template #xp>
                    <Card title="XP mukofotlari" description="Har bir faoliyat uchun beriladigan tajriba ballari">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Input v-for="f in FIELDS.xp" :key="f.key" v-model.number="localSettings.xp[f.key]" type="number" min="0"
                                :step="f.step || '1'" inputmode="decimal" :label="f.label" :hint="hintOf('xp', f.key)">
                                <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                            </Input>
                        </div>
                    </Card>
                </template>

                <template #coin>
                    <div class="space-y-6">
                        <Card title="Coin mukofotlari">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-for="f in FIELDS.coin" :key="f.key" v-model.number="localSettings.coin[f.key]" type="number" min="0"
                                    inputmode="numeric" :label="f.label" :hint="hintOf('coin', f.key)">
                                    <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                                </Input>
                            </div>
                        </Card>
                        <Card title="Oylik coin limitlari" description="Obuna turiga qarab bir oyda yig'ish mumkin bo'lgan maksimum">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-for="f in FIELDS.coinLimits" :key="f.key" v-model.number="localSettings.coin[f.key]" type="number"
                                    min="0" inputmode="numeric" :label="f.label" :hint="hintOf('coin', f.key)">
                                    <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                                </Input>
                            </div>
                        </Card>
                    </div>
                </template>

                <template #battle>
                    <Card title="Battle parametrlari">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Input v-for="f in FIELDS.battle" :key="f.key" v-model.number="localSettings.battle[f.key]" type="number" min="0"
                                inputmode="numeric" :label="f.label" :hint="hintOf('battle', f.key)">
                                <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                            </Input>
                        </div>
                    </Card>
                </template>
            </Tabs>

            <div v-if="dirty" class="mt-6 flex justify-end gap-2">
                <Button variant="secondary" :disabled="saving" @click="resetChanges">Bekor qilish</Button>
                <Button type="submit" :icon-left="CheckIcon" :loading="saving">Saqlash</Button>
            </div>
        </form>
    </AdminLayout>
</template>
