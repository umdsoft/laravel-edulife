<template>
    <AdminLayout>

        <Head title="Leaderboard Sozlamalari" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Leaderboard</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Settings -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Sozlamalar</h2>

                    <form @submit.prevent="updateSettings" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Davr</label>
                            <select v-model="form.period"
                                class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20">
                                <option value="weekly">Haftalik</option>
                                <option value="monthly">Oylik</option>
                                <option value="all_time">Umumiy</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_active"
                                class="rounded text-primary focus:ring-primary" />
                            <span class="text-sm text-gray-700">Faol</span>
                        </div>

                        <div class="pt-4">
                            <Button :loading="form.processing" class="w-full justify-center">Saqlash</Button>
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <h3 class="text-sm font-medium text-red-600 mb-2">Xavfli hudud</h3>
                        <p class="text-xs text-gray-500 mb-4">
                            Leaderboardni majburiy yangilash barcha foydalanuvchilarning davriy ballarini 0 ga
                            tushiradi.
                        </p>
                        <Button variant="danger" class="w-full justify-center" @click="resetLeaderboard">
                            Leaderboardni yangilash
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Top 10 (Hozirgi holat)</h2>

                    <div class="space-y-3">
                        <div v-for="(user, index) in topUsers" :key="user.id"
                            class="flex items-center justify-between p-3 rounded-xl" :class="{
                                'bg-yellow-50 border border-yellow-100': index === 0,
                                'bg-gray-50': index > 0
                            }">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                                    :class="{
                                        'bg-yellow-100 text-yellow-700': index === 0,
                                        'bg-gray-200 text-gray-600': index > 0
                                    }">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</p>
                                    <p class="text-xs text-gray-500">XP: {{ user.xp }}</p>
                                </div>
                            </div>
                            <div v-if="index === 0" class="text-2xl">👑</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    topUsers: Array,
    settings: Object,
});

const form = useForm({
    period: props.settings.period,
    is_active: props.settings.is_active,
});

const updateSettings = () => {
    form.put('/admin/leaderboard');
};

const resetLeaderboard = () => {
    if (confirm('Rostdan ham leaderboardni yangilamoqchimisiz? Bu amalni ortga qaytarib bo\'lmaydi.')) {
        router.post('/admin/leaderboard/reset');
    }
};
</script>
