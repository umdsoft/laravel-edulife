<template>
    <AdminLayout>

        <Head title="Sozlamalar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tizim sozlamalari</h1>
                <p class="text-gray-500">Platformaning asosiy parametrlarini boshqarish</p>
            </div>

            <Button variant="primary" @click="saveSettings" :loading="saving">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Saqlash
            </Button>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-sm">
            <div class="border-b border-gray-100">
                <nav class="flex gap-6 px-6">
                    <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                        'py-4 border-b-2 font-medium text-sm transition',
                        activeTab === tab.key
                            ? 'border-primary text-primary'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- XP Settings -->
                <div v-if="activeTab === 'xp'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">XP Sozlamalari</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <Input v-model.number="localSettings.xp.xp_per_lesson" type="number" label="Dars uchun XP" />
                        <Input v-model.number="localSettings.xp.xp_per_test" type="number" label="Test uchun XP" />
                        <Input v-model.number="localSettings.xp.xp_per_battle" type="number"
                            label="Battle g'alaba uchun XP" />
                        <Input v-model.number="localSettings.xp.premium_xp_multiplier" type="number" step="0.1"
                            label="Premium XP ko'paytiruvchi" />
                    </div>
                </div>

                <!-- COIN Settings -->
                <div v-if="activeTab === 'coin'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">COIN Sozlamalari</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <Input v-model.number="localSettings.coin.coin_per_lesson" type="number"
                            label="Dars uchun COIN" />
                        <Input v-model.number="localSettings.coin.coin_per_test" type="number"
                            label="Test uchun COIN" />
                        <Input v-model.number="localSettings.coin.coin_per_battle" type="number"
                            label="Battle g'alaba uchun COIN" />
                        <Input v-model.number="localSettings.coin.transfer_fee_percent" type="number"
                            label="Transfer fee (%)" />
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Oylik COIN limitlar (obuna bo'yicha)</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <Input v-model.number="localSettings.coin.monthly_limit_trial" type="number"
                                label="Trial" />
                            <Input v-model.number="localSettings.coin.monthly_limit_standard" type="number"
                                label="Standart" />
                            <Input v-model.number="localSettings.coin.monthly_limit_premium" type="number"
                                label="Premium" />
                            <Input v-model.number="localSettings.coin.monthly_limit_vip" type="number" label="VIP" />
                        </div>
                    </div>
                </div>

                <!-- Battle Settings -->
                <div v-if="activeTab === 'battle'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Battle Sozlamalari</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <Input v-model.number="localSettings.battle.questions_per_match" type="number"
                            label="Savollar soni (har bir match)" />
                        <Input v-model.number="localSettings.battle.time_per_question" type="number"
                            label="Har bir savol uchun vaqt (soniya)" />
                        <Input v-model.number="localSettings.battle.daily_limit_trial" type="number"
                            label="Kunlik limit (Trial)" />
                        <Input v-model.number="localSettings.battle.elo_k_factor" type="number"
                            label="ELO K koeffitsienti" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('xp');
const saving = ref(false);

const tabs = [
    { key: 'xp', label: 'XP' },
    { key: 'coin', label: 'COIN' },
    { key: 'battle', label: 'Battle' },
];

// Local editable settings
const localSettings = reactive({
    xp: {
        xp_per_lesson: props.settings.xp?.xp_per_lesson?.value || 30,
        xp_per_test: props.settings.xp?.xp_per_test?.value || 100,
        xp_per_battle: props.settings.xp?.xp_per_battle?.value || 150,
        premium_xp_multiplier: props.settings.xp?.premium_xp_multiplier?.value || 1.5,
    },
    coin: {
        coin_per_lesson: props.settings.coin?.coin_per_lesson?.value || 5,
        coin_per_test: props.settings.coin?.coin_per_test?.value || 25,
        coin_per_battle: props.settings.coin?.coin_per_battle?.value || 35,
        transfer_fee_percent: props.settings.coin?.transfer_fee_percent?.value || 5,
        monthly_limit_trial: props.settings.coin?.monthly_limit_trial?.value || 1500,
        monthly_limit_standard: props.settings.coin?.monthly_limit_standard?.value || 2235,
        monthly_limit_premium: props.settings.coin?.monthly_limit_premium?.value || 4980,
        monthly_limit_vip: props.settings.coin?.monthly_limit_vip?.value || 9975,
    },
    battle: {
        questions_per_match: props.settings.battle?.questions_per_match?.value || 5,
        time_per_question: props.settings.battle?.time_per_question?.value || 20,
        daily_limit_trial: props.settings.battle?.daily_limit_trial?.value || 3,
        elo_k_factor: props.settings.battle?.elo_k_factor?.value || 32,
    },
});

const saveSettings = () => {
    saving.value = true;

    // Flatten settings for backend
    const settingsArray = [];

    Object.keys(localSettings).forEach(group => {
        Object.keys(localSettings[group]).forEach(key => {
            settingsArray.push({
                key: `${group}.${key}`,
                value: localSettings[group][key],
            });
        });
    });

    router.put('/admin/settings', {
        settings: settingsArray,
    }, {
        onFinish: () => {
            saving.value = false;
        },
    });
};
</script>
