<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import StatsCard from '@/Components/Teacher/StatsCard.vue';
import EarningsChart from '@/Components/Teacher/EarningsChart.vue';
import Modal from '@/Components/UI/Modal.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    balance: Number,
    stats: Object,
    recentEarnings: Array,
    payouts: Array,
    bankAccounts: Array,
    monthlyChart: Array,
    commissionRate: Number,
});

const showWithdrawModal = ref(false);
const withdrawForm = useForm({
    amount: '',
    bank_account_id: '',
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount);
};

const submitWithdraw = () => {
    withdrawForm.post(route('teacher.earnings.withdraw'), {
        onSuccess: () => {
            showWithdrawModal.value = false;
            withdrawForm.reset();
        },
    });
};

const setMaxAmount = () => {
    withdrawForm.amount = props.balance;
};
</script>

<template>

    <Head title="Daromadlar" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Daromadlar</h2>
        </template>

        <div class="space-y-6">
            <!-- Balance Card -->
            <div class="p-6 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl shadow-lg">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <p class="text-emerald-100 font-medium mb-1">Joriy balans</p>
                        <h2 class="text-4xl font-bold mb-2">{{ formatMoney(balance) }} UZS</h2>
                        <div class="flex items-center gap-2 text-sm text-emerald-100">
                            <span class="bg-white/20 px-2 py-0.5 rounded">Komissiya: {{ commissionRate }}%</span>
                            <span>(Sotuvdan sizga qoladigan qism)</span>
                        </div>
                    </div>
                    <button @click="showWithdrawModal = true"
                        class="px-6 py-3 bg-white text-emerald-600 font-bold rounded-xl hover:bg-emerald-50 transition-colors shadow-sm">
                        Mablag'ni yechib olish
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatsCard title="Jami daromad" :value="formatMoney(stats.total_earned)" icon="💰" />
                <StatsCard title="Bu oy" :value="formatMoney(stats.this_month)" icon="📅" trend="+15%"
                    :trendUp="true" />
                <StatsCard title="Kutilmoqda" :value="formatMoney(stats.pending_payout)" icon="⏳" />
                <StatsCard title="Yechib olingan" :value="formatMoney(stats.total_withdrawn)" icon="✅" />
            </div>

            <!-- Chart -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Daromadlar statistikasi</h3>
                <EarningsChart :data="monthlyChart.map(d => ({ month: d.month, amount: d.earnings }))" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Earnings -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900">So'nggi tushumlar</h3>
                        <Link href="/teacher/earnings/history"
                            class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Hammasi</Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="earning in recentEarnings" :key="earning.id"
                            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition-colors">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold">
                                    +
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ formatMoney(earning.amount) }} UZS</p>
                                    <p class="text-xs text-gray-500">{{ earning.course.title }} • {{
                                        earning.user.first_name }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">
                                {{ new Date(earning.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                        <div v-if="recentEarnings.length === 0" class="text-center text-gray-500 py-4">
                            Hali daromadlar yo'q
                        </div>
                    </div>
                </div>

                <!-- Payouts -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Yechib olishlar tarixi</h3>
                    <div class="space-y-4">
                        <div v-for="payout in payouts" :key="payout.id"
                            class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-xl transition-colors">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600">
                                    🏦
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ formatMoney(payout.amount) }} UZS</p>
                                    <p class="text-xs text-gray-500">{{ payout.bank_account.bank_name }} • {{
                                        payout.bank_account.account_number.slice(-4) }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span :class="{
                                    'text-yellow-600 bg-yellow-50': payout.status === 'pending',
                                    'text-green-600 bg-green-50': payout.status === 'completed',
                                    'text-red-600 bg-red-50': payout.status === 'rejected',
                                }" class="px-2 py-1 rounded text-xs font-medium capitalize">
                                    {{ payout.status }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ new Date(payout.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                        <div v-if="payouts.length === 0" class="text-center text-gray-500 py-4">
                            Yechib olishlar tarixi bo'sh
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Withdraw Modal -->
        <Modal :show="showWithdrawModal" @close="showWithdrawModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Mablag'ni yechib olish</h3>

                <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-500 mb-1">Mavjud balans</p>
                    <p class="text-2xl font-bold text-gray-900">{{ formatMoney(balance) }} UZS</p>
                </div>

                <form @submit.prevent="submitWithdraw" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Summa</label>
                        <div class="relative">
                            <input v-model="withdrawForm.amount" type="number"
                                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="0" required>
                            <button type="button" @click="setMaxAmount"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-medium text-emerald-600 hover:text-emerald-700 px-2 py-1 bg-emerald-50 rounded">
                                Hammasi
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimal summa: 100,000 UZS</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank hisobi</label>
                        <select v-model="withdrawForm.bank_account_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            required>
                            <option value="" disabled>Tanlang</option>
                            <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.bank_name }} • {{ acc.account_number.slice(-4) }}
                            </option>
                        </select>
                        <p v-if="bankAccounts.length === 0" class="text-xs text-red-500 mt-1">
                            Tasdiqlangan bank hisobi yo'q.
                            <Link href="/teacher/bank-accounts" class="underline">Qo'shish</Link>
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showWithdrawModal = false"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl">
                            Bekor qilish
                        </button>
                        <Button type="submit" :loading="withdrawForm.processing" :disabled="bankAccounts.length === 0">
                            Yechib olish
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </TeacherLayout>
</template>
