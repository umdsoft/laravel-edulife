<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    tournaments: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>

    <Head title="Turnirlar" />

    <StudentLayout>
        <div class="max-w-5xl mx-auto py-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <Link :href="route('student.battles.index')"
                        class="text-sm text-purple-600 hover:underline mb-1 inline-block">
                    &larr; Janglarga qaytish
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">Turnirlar</h1>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="tournament in tournaments.data" :key="tournament.id"
                    class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow group">
                    <div
                        class="h-32 bg-gradient-to-r from-yellow-400 to-orange-500 relative p-6 flex flex-col justify-end">
                        <h3 class="text-xl font-bold text-white relative z-10">{{ tournament.title }}</h3>
                        <div
                            class="absolute top-4 right-4 bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-xs font-bold">
                            {{ tournament.status }}
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4 text-sm text-gray-500">
                            <span>Boshlanish vaqti:</span>
                            <span class="font-medium text-gray-900">{{ formatDate(tournament.start_date) }}</span>
                        </div>

                        <div class="flex justify-between items-center mb-6 text-sm text-gray-500">
                            <span>Ishtirokchilar:</span>
                            <span class="font-medium text-gray-900">{{ tournament.participants_count || 0 }} / {{
                                tournament.max_participants }}</span>
                        </div>

                        <Link :href="route('student.tournaments.show', tournament.id)"
                            class="block w-full py-3 bg-gray-50 text-gray-900 font-bold text-center rounded-xl hover:bg-gray-100 transition-colors">
                        Batafsil
                        </Link>
                    </div>
                </div>
            </div>

            <Pagination :data="tournaments" class="mt-8" />
        </div>
    </StudentLayout>
</template>
