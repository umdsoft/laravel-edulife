<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ScoreBreakdown from '@/Components/Teacher/ScoreBreakdown.vue';
import ScoreChart from '@/Components/Teacher/ScoreChart.vue';
import Modal from '@/Components/UI/Modal.vue';
import { format } from 'date-fns';
import { uz } from 'date-fns/locale';

const props = defineProps({
    teacher: Object,
    score: Number,
    level: String,
    breakdown: Object,
    history: Array,
    levelChanges: Array,
});

const showOverrideModal = ref(false);
const overrideForm = useForm({
    level: props.level,
    reason: '',
});

const submitOverride = () => {
    overrideForm.post(route('admin.teacher-ratings.override', props.teacher.id), {
        onSuccess: () => {
            showOverrideModal.value = false;
            overrideForm.reset();
        },
    });
};

const recalculate = () => {
    if (confirm('Score qayta hisoblanadi. Davom etasizmi?')) {
        router.post(route('admin.teacher-ratings.recalculate', props.teacher.id));
    }
};

const getLevelName = (level) => {
    switch (level) {
        case 'top': return 'TOP';
        case 'featured': return 'FEATURED';
        case 'verified': return 'VERIFIED';
        default: return 'NEW';
    }
};

const getLevelColor = (level) => {
    switch (level) {
        case 'top': return 'text-yellow-600 bg-yellow-100';
        case 'featured': return 'text-purple-600 bg-purple-100';
        case 'verified': return 'text-blue-600 bg-blue-100';
        default: return 'text-gray-600 bg-gray-100';
    }
};
</script>

<template>
    <AdminLayout>

        <Head :title="`${teacher.first_name} ${teacher.last_name} - Reyting`" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <Link :href="route('admin.teacher-ratings.index')" class="text-gray-500 hover:text-gray-700 mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ teacher.first_name }} {{ teacher.last_name }}
                        </h1>
                        <p class="text-sm text-gray-500">{{ teacher.email }}</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button @click="recalculate"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Qayta hisoblash
                    </button>
                    <button @click="showOverrideModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Darajani o'zgartirish
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Status -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Joriy Score</div>
                                <div class="mt-1 text-3xl font-bold text-gray-900">{{ score }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Joriy Level</div>
                                <div class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-bold"
                                    :class="getLevelColor(level)">
                                    {{ getLevelName(level) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Komissiya</div>
                                <div class="mt-1 text-2xl font-bold text-green-600">{{
                                    teacher.teacher_profile?.commission_rate }}%</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Oxirgi yangilanish</div>
                                <div class="mt-1 text-sm text-gray-900">
                                    {{ teacher.teacher_profile?.score_updated_at ? format(new
                                        Date(teacher.teacher_profile.score_updated_at), 'dd MMM, yyyy HH:mm', { locale: uz
                                    }) : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score History Chart -->
                    <ScoreChart :history="history" />

                    <!-- Level Changes -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Daraja O'zgarishlari</h3>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <li v-for="(change, changeIdx) in levelChanges" :key="change.id">
                                    <div class="relative pb-8">
                                        <span v-if="changeIdx !== levelChanges.length - 1"
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                                                    :class="change.new_score > change.old_score ? 'bg-green-500' : 'bg-red-500'">
                                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path v-if="change.new_score > change.old_score"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                        <path v-else stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500">
                                                        <span class="font-medium text-gray-900">{{
                                                            getLevelName(change.old_level) }}</span> dan
                                                        <span class="font-medium text-gray-900">{{
                                                            getLevelName(change.new_level) }}</span> ga o'zgardi
                                                    </p>
                                                    <p v-if="change.reason" class="text-xs text-gray-600 mt-1">
                                                        Sabab: "{{ change.reason }}" ({{
                                                        change.changed_by_user?.first_name }} tomonidan)
                                                    </p>
                                                    <p v-else class="text-xs text-gray-400 mt-1">Avtomatik</p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                    <time :datetime="change.created_at">{{ format(new
                                                        Date(change.created_at), 'dd MMM', { locale: uz }) }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <ScoreBreakdown :breakdown="breakdown" />
                </div>
            </div>

            <!-- Override Modal -->
            <Modal :show="showOverrideModal" @close="showOverrideModal = false">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Darajani qo'lda o'zgartirish</h2>
                    <p class="text-sm text-gray-500 mb-4">
                        Diqqat! Bu amal avtomatik hisoblashni vaqtinchalik chetlab o'tadi.
                        O'qituvchi darajasi keyingi avtomatik hisoblashda yana o'zgarishi mumkin, agar "Sabab" maydonida
                        maxsus izoh qoldirilmasa.
                    </p>

                    <form @submit.prevent="submitOverride" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Yangi Daraja</label>
                            <select v-model="overrideForm.level"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="new">🆕 NEW</option>
                                <option value="verified">✓ VERIFIED</option>
                                <option value="featured">⭐ FEATURED</option>
                                <option value="top">🏆 TOP</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sabab (Majburiy)</label>
                            <textarea v-model="overrideForm.reason" rows="3"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required placeholder="Nima uchun daraja o'zgartirilmoqda?"></textarea>
                            <div v-if="overrideForm.errors.reason" class="text-red-500 text-xs mt-1">{{
                                overrideForm.errors.reason }}</div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showOverrideModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Bekor qilish
                            </button>
                            <button type="submit" :disabled="overrideForm.processing"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                                O'zgartirish
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>
