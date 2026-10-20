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
// defaults apply only when a setting is missing and equal the reward code's defaults (RewardSettings).
const FIELDS = {
    xp: [
        { key: 'xp_per_lesson', label: 'Dars uchun', unit: 'XP', default: 8 },
        { key: 'xp_per_test_pass', label: 'Test uchun', unit: 'XP', default: 40 },
        { key: 'xp_per_battle_win', label: "Battle g'alabasi uchun", unit: 'XP', default: 100 },
        { key: 'xp_multiplier_premium', label: "Premium ko'paytiruvchi", unit: '×', default: 1, step: '0.1' },
        { key: 'xp_multiplier_vip', label: "VIP ko'paytiruvchi", unit: '×', default: 1, step: '0.1' },
    ],
    coin: [
        { key: 'coin_per_lesson', label: 'Dars uchun', unit: 'coin', default: 0 },
        { key: 'coin_per_test_pass', label: 'Test uchun', unit: 'coin', default: 0 },
        { key: 'coin_per_battle_win', label: "Battle g'alabasi uchun", unit: 'coin', default: 10 },
    ],
    coinLimits: [
        { key: 'coin_monthly_limit_trial', label: 'Obunasiz (Trial)', unit: 'coin', default: 0 },
        { key: 'coin_monthly_limit_standart', label: 'Standart', unit: 'coin', default: 0 },
        { key: 'coin_monthly_limit_premium', label: 'Premium', unit: 'coin', default: 0 },
        { key: 'coin_monthly_limit_vip', label: 'VIP', unit: 'coin', default: 0 },
    ],
    battle: [
        { key: 'battle_questions_count', label: "Bir o'yindagi savollar", unit: 'ta', default: 5 },
        { key: 'battle_time_per_question', label: 'Har bir savolga vaqt', unit: 'soniya', default: 20 },
        { key: 'elo_k_factor', label: 'ELO K koeffitsienti', unit: 'K', default: 20 },
    ],
    battleScoring: [
        { key: 'battle_scoring_base', label: 'Savolning asosiy qiymati', unit: 'ochko', default: 100, step: 1 },
        { key: 'battle_scoring_k', label: 'Qiyinlik koeffitsiyenti (k)', unit: 'k', default: 2, step: 0.1 },
        { key: 'battle_scoring_speed_bonus', label: 'Tezlik bonusi (maks.)', unit: 'ulush', default: 0.2, step: 0.05 },
        { key: 'battle_scoring_solver_exponent', label: 'Yechganlar soni darajasi', unit: 'daraja', default: 1, step: 0.1 },
        { key: 'battle_scoring_prior_strength', label: 'Yangi savol barqarorligi (m)', unit: 'urinish', default: 10, step: 1 },
        { key: 'battle_scoring_test_weight', label: "Kurs testi javoblari og'irligi", unit: 'ulush', default: 0.5, step: 0.05 },
        { key: 'battle_scoring_prior_easy', label: "Boshlang'ich yechilish: Oson", unit: 'ulush', default: 0.8, step: 0.01 },
        { key: 'battle_scoring_prior_medium', label: "Boshlang'ich yechilish: O'rta", unit: 'ulush', default: 0.6, step: 0.01 },
        { key: 'battle_scoring_prior_hard', label: "Boshlang'ich yechilish: Qiyin", unit: 'ulush', default: 0.35, step: 0.01 },
        { key: 'battle_level_easy_min_p', label: "'Oson' daraja chegarasi", unit: 'ulush', default: 0.7, step: 0.01 },
        { key: 'battle_level_hard_max_p', label: "'Qiyin' daraja chegarasi", unit: 'ulush', default: 0.4, step: 0.01 },
        { key: 'battle_elo_medium_from', label: "Avtomatik 'O'rta' ELO'dan", unit: 'ELO', default: 1200, step: 10 },
        { key: 'battle_elo_hard_from', label: "Avtomatik 'Qiyin' ELO'dan", unit: 'ELO', default: 1500, step: 10 },
        { key: 'battle_recent_battles', label: 'Takrorlanmaslik oynasi', unit: 'battle', default: 20, step: 1 },
    ],
    battleLimits: [
        { key: 'battle_daily_limit_trial', label: 'Obunasiz (Trial)', unit: "o'yin", default: 0 },
        { key: 'battle_daily_limit_standart', label: 'Standart', unit: "o'yin", default: 0 },
        { key: 'battle_daily_limit_premium', label: 'Premium', unit: "o'yin", default: 0 },
        { key: 'battle_daily_limit_vip', label: 'VIP', unit: "o'yin", default: 0 },
    ],
};
const groupOf = (section) => ({ coinLimits: 'coin', battleLimits: 'battle', battleScoring: 'battle' })[section] ?? section;
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
                        <Card title="Oylik coin limitlari" description="Obuna turiga qarab bir oyda mukofotlardan yig'ish mumkin bo'lgan maksimum (0 = cheksiz)">
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
                    <div class="space-y-6">
                        <Card title="Battle parametrlari">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-for="f in FIELDS.battle" :key="f.key" v-model.number="localSettings.battle[f.key]" type="number" min="0"
                                    inputmode="numeric" :label="f.label" :hint="hintOf('battle', f.key)">
                                    <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                                </Input>
                            </div>
                        </Card>
                        <Card title="Battle ballari" description="Savol qiymati qiyinlikka qarab: hamma yechadigan savol kam, kam yechiladigan savol ko'p ochko beradi">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-for="f in FIELDS.battleScoring" :key="f.key" v-model.number="localSettings.battle[f.key]" type="number" min="0"
                                    :step="f.step" inputmode="decimal" :label="f.label" :hint="hintOf('battle', f.key)">
                                    <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                                </Input>
                            </div>
                        </Card>
                        <Card title="Kunlik battle limitlari" description="Obuna turiga qarab bir kunda boshlash mumkin bo'lgan o'yinlar (0 = cheksiz)">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-for="f in FIELDS.battleLimits" :key="f.key" v-model.number="localSettings.battle[f.key]" type="number"
                                    min="0" inputmode="numeric" :label="f.label" :hint="hintOf('battle', f.key)">
                                    <template #trailing><span class="mr-2 text-13 text-fg-subtle">{{ f.unit }}</span></template>
                                </Input>
                            </div>
                        </Card>
                    </div>
                </template>
            </Tabs>

            <div v-if="dirty" class="mt-6 flex justify-end gap-2">
                <Button variant="secondary" :disabled="saving" @click="resetChanges">Bekor qilish</Button>
                <Button type="submit" :icon-left="CheckIcon" :loading="saving">Saqlash</Button>
            </div>
        </form>
    </AdminLayout>
</template>
