<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    types: Array,
    stages: Array,
    series: Array,
    regions: Array,
});

const form = useForm({
    title: '',
    description: '',
    short_description: '',
    rules: '',
    olympiad_type_id: '',
    stage_id: '',
    series_id: '',
    region_id: '',
    registration_fee: 0,
    registration_start_at: '',
    registration_end_at: '',
    olympiad_start_at: '',
    olympiad_end_at: '',
    max_participants: null,
    demo_config: {
        enabled: false,
        price: 0,
        max_attempts: 3,
    },
    anti_cheat_config: {
        fullscreen_required: true,
        device_lock: true,
        max_tab_switches: 3,
        max_warnings: 5,
    },
    reward_config: {
        coins_per_correct: 1,
        top_10_coins: 100,
    },
    create_default_sections: true,
});

const activeTab = ref('basic');

const submit = () => {
    form.post(route('admin.olympiads.store'));
};
</script>

<template>
    <AdminLayout>
        <Head title="Yangi olimpiada" />
        
        <div class="py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <Link :href="route('admin.olympiads.index')" class="text-purple-600 hover:underline text-sm mb-2 inline-block">
                            ← Olimpiadalar
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">➕ Yangi olimpiada</h1>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Tabs -->
                    <div class="flex gap-2 mb-6 overflow-x-auto">
                        <button 
                            v-for="tab in ['basic', 'schedule', 'config', 'rewards']" 
                            :key="tab"
                            type="button"
                            @click="activeTab = tab"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap',
                                activeTab === tab 
                                    ? 'bg-purple-600 text-white' 
                                    : 'bg-white dark:bg-gray-800 text-gray-600 hover:bg-gray-50'
                            ]">
                            {{ tab === 'basic' ? '📋 Asosiy' : tab === 'schedule' ? '📅 Jadval' : tab === 'config' ? '⚙️ Sozlamalar' : '🎁 Mukofotlar' }}
                        </button>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <!-- Basic Tab -->
                        <div v-show="activeTab === 'basic'" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nomi *</label>
                                <input 
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    placeholder="Matematika olimpiadasi 2024">
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Turi *</label>
                                    <select 
                                        v-model="form.olympiad_type_id"
                                        required
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="">Tanlang...</option>
                                        <option v-for="type in types" :key="type.id" :value="type.id">
                                            {{ type.display_name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bosqich</label>
                                    <select 
                                        v-model="form.stage_id"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="">Tanlang...</option>
                                        <option v-for="stage in stages" :key="stage.id" :value="stage.id">
                                            {{ stage.display_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Seriya</label>
                                    <select 
                                        v-model="form.series_id"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="">Tanlang...</option>
                                        <option v-for="s in series" :key="s.id" :value="s.id">
                                            {{ s.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Viloyat</label>
                                    <select 
                                        v-model="form.region_id"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                        <option value="">Barcha viloyatlar</option>
                                        <option v-for="region in regions" :key="region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qisqa tavsif</label>
                                <textarea 
                                    v-model="form.short_description"
                                    rows="2"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    placeholder="500 belgigacha..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To'liq tavsif</label>
                                <textarea 
                                    v-model="form.description"
                                    rows="5"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Qoidalar</label>
                                <textarea 
                                    v-model="form.rules"
                                    rows="4"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                            </div>
                        </div>

                        <!-- Schedule Tab -->
                        <div v-show="activeTab === 'schedule'" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ro'yxat boshlanishi</label>
                                    <input 
                                        v-model="form.registration_start_at"
                                        type="datetime-local"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ro'yxat tugashi</label>
                                    <input 
                                        v-model="form.registration_end_at"
                                        type="datetime-local"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Olimpiada boshlanishi *</label>
                                    <input 
                                        v-model="form.olympiad_start_at"
                                        type="datetime-local"
                                        required
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Olimpiada tugashi *</label>
                                    <input 
                                        v-model="form.olympiad_end_at"
                                        type="datetime-local"
                                        required
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ro'yxatdan o'tish narxi</label>
                                    <div class="relative">
                                        <input 
                                            v-model.number="form.registration_fee"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 pr-16">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">so'm</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maksimum ishtirokchilar</label>
                                    <input 
                                        v-model.number="form.max_participants"
                                        type="number"
                                        min="1"
                                        placeholder="Cheklanmagan"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                </div>
                            </div>
                        </div>

                        <!-- Config Tab -->
                        <div v-show="activeTab === 'config'" class="space-y-6">
                            <!-- Demo Config -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">🎯 Demo test</h3>
                                <div class="space-y-4">
                                    <label class="flex items-center gap-3">
                                        <input 
                                            v-model="form.demo_config.enabled"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span class="text-gray-700 dark:text-gray-300">Demo testni yoqish</span>
                                    </label>
                                    <div v-if="form.demo_config.enabled" class="grid sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Demo narxi</label>
                                            <input 
                                                v-model.number="form.demo_config.price"
                                                type="number"
                                                min="0"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Maks urinishlar</label>
                                            <input 
                                                v-model.number="form.demo_config.max_attempts"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Anti-Cheat Config -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">🔒 Anti-cheat sozlamalari</h3>
                                <div class="space-y-3">
                                    <label class="flex items-center gap-3">
                                        <input 
                                            v-model="form.anti_cheat_config.fullscreen_required"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span class="text-gray-700 dark:text-gray-300">Fullscreen talab qilish</span>
                                    </label>
                                    <label class="flex items-center gap-3">
                                        <input 
                                            v-model="form.anti_cheat_config.device_lock"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span class="text-gray-700 dark:text-gray-300">Qurilmani qulflash</span>
                                    </label>
                                    <div class="grid sm:grid-cols-2 gap-4 mt-4">
                                        <div>
                                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Maks tab almashtirishlar</label>
                                            <input 
                                                v-model.number="form.anti_cheat_config.max_tab_switches"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Maks ogohlantirishlar</label>
                                            <input 
                                                v-model.number="form.anti_cheat_config.max_warnings"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Default Sections -->
                            <label class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <input 
                                    v-model="form.create_default_sections"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-gray-700 dark:text-gray-300">Standart bo'limlarni yaratish (turi asosida)</span>
                            </label>
                        </div>

                        <!-- Rewards Tab -->
                        <div v-show="activeTab === 'rewards'" class="space-y-6">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">🪙 Coin mukofotlari</h3>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Har bir to'g'ri javob</label>
                                        <input 
                                            v-model.number="form.reward_config.coins_per_correct"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Top 10 uchun bonus</label>
                                        <input 
                                            v-model.number="form.reward_config.top_10_coins"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-600">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link 
                                :href="route('admin.olympiads.index')"
                                class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                                Bekor qilish
                            </Link>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 bg-purple-600 text-white rounded-xl font-medium hover:bg-purple-700 transition-colors disabled:opacity-50">
                                {{ form.processing ? 'Saqlanmoqda...' : 'Yaratish' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
