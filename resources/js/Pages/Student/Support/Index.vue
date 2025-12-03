<template>
    <StudentLayout title="Yordam">
        <div class="max-w-6xl mx-auto py-6 px-4">

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Stats -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                    <div class="text-4xl font-bold">{{ stats.open }}</div>
                    <div class="text-sm">Ochiq so'rovlar</div>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                    <div class="text-4xl font-bold">{{ stats.resolved }}</div>
                    <div class="text-sm">Hal qilingan</div>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                    <div class="text-4xl font-bold">{{ stats.total }}</div>
                    <div class="text-sm">Jami so'rovlar</div>
                </div>
            </div>

            <div class="flex justify-between items-center my-6">
                <h1 class="text-2xl font-bold">Yordam So'rovlari</h1>
                <div class="flex gap-2">
                    <Link :href="route('student.support.faq')" class="btn-secondary">
                    ❓ FAQ
                    </Link>
                    <Link :href="route('student.support.create')" class="btn-primary">
                    ➕ Yangi So'rov
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div v-if="tickets.data.length === 0" class="p-12 text-center text-gray-500">
                    Hech qanday so'rov yo'q
                </div>

                <div v-else class="divide-y">
                    <Link v-for="ticket in tickets.data" :key="ticket.id"
                        :href="route('student.support.show', ticket.id)" class="block p-4 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm text-gray-600">{{ ticket.ticket_number }}</span>
                                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(ticket.status)">
                                    {{ ticket.status_label }}
                                </span>
                            </div>
                            <h3 class="font-semibold mt-1">{{ ticket.subject }}</h3>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ ticket.description }}</p>
                            <div class="flex items-center gap-3 text-xs text-gray-500 mt-2">
                                <span>📁 {{ ticket.category_label }}</span>
                                <span>💬 {{ ticket.messages_count || 0 }} xabar</span>
                                <span>{{ formatDate(ticket.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links" class="p-4 border-t flex gap-2">
                    <Link v-for="link in tickets.links" :key="link.label" :href="link.url"
                        :class="['px-3 py-1 rounded', link.active ? 'bg-purple-600 text-white' : 'bg-gray-100']"
                        v-html="link.label">
                    </Link>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tickets: Object,
    stats: Object,
});

const getStatusClass = (status) => {
    const classes = {
        open: 'bg-green-100 text-green-700',
        in_progress: 'bg-blue-100 text-blue-700',
        waiting_user: 'bg-yellow-100 text-yellow-700',
        resolved: 'bg-purple-100 text-purple-700',
        closed: 'bg-gray-100 text-gray-700',
    };
    return classes[status] || 'bg-gray-100';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uz-UZ');
};
</script>
