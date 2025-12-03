<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Badge from '@/Components/Admin/Badge.vue';

const props = defineProps({
    teachers: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const level = ref(props.filters.level || '');

const getLevelBadge = (level) => {
    switch (level) {
        case 'top': return 'warning';
        case 'featured': return 'purple';
        case 'verified': return 'success';
        default: return 'gray';
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

let timeout = null;

watch([search, level], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.teacher-ratings.index'), {
            search: search.value,
            level: level.value,
        }, { preserveState: true, replace: true });
    }, 300);
});

const recalculateAll = () => {
    if (confirm('Barcha o\'qituvchilar uchun score qayta hisoblanadi. Davom etasizmi?')) {
        router.post(route('admin.teacher-ratings.recalculate-all'));
    }
};
</script>

<template>
    <AdminLayout>

        <Head title="O'qituvchilar Reytingi" />

        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold text-gray-900">O'qituvchilar Reytingi</h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Barcha o'qituvchilarning reytingi, darajasi va komissiya stavkalari.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <button @click="recalculateAll"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                        Hammasini hisoblash
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Jami O'qituvchilar</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ stats.total }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">🆕 NEW</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ stats.new }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">✓ VERIFIED</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">{{ stats.verified }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">⭐ FEATURED</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-purple-600">{{ stats.featured }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">🏆 TOP</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">{{ stats.top }}</dd>
                </div>
            </div>

            <!-- Filters -->
            <div class="mt-8 flex flex-col sm:flex-row gap-4">
                <div class="relative max-w-xs">
                    <input v-model="search" type="text"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Qidirish..." />
                </div>
                <div class="relative max-w-xs">
                    <select v-model="level"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Barcha darajalar</option>
                        <option value="new">🆕 NEW</option>
                        <option value="verified">✓ VERIFIED</option>
                        <option value="featured">⭐ FEATURED</option>
                        <option value="top">🏆 TOP</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="mt-8 flex flex-col">
                <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                                            O'qituvchi
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Score
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Level
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                            Komissiya
                                        </th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="teacher in teachers.data" :key="teacher.id">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <img class="h-10 w-10 rounded-full"
                                                        :src="teacher.avatar_url || `https://ui-avatars.com/api/?name=${teacher.first_name}+${teacher.last_name}`"
                                                        alt="" />
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-gray-900">{{ teacher.first_name }} {{
                                                        teacher.last_name }}</div>
                                                    <div class="text-gray-500">{{ teacher.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span class="font-bold text-gray-900">{{ teacher.teacher_profile?.score || 0
                                                }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <Badge :color="getLevelBadge(teacher.teacher_profile?.level)">
                                                {{ getLevelName(teacher.teacher_profile?.level) }}
                                            </Badge>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            {{ teacher.teacher_profile?.commission_rate }}%
                                        </td>
                                        <td
                                            class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                            <Link :href="route('admin.teacher-ratings.show', teacher.id)"
                                                class="text-indigo-600 hover:text-indigo-900">
                                            Ko'rish<span class="sr-only">, {{ teacher.first_name }}</span>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Pagination :data="teachers" class="mt-6" />
        </div>
    </AdminLayout>
</template>
