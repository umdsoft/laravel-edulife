<template>
    <StudentLayout title="Do'stlar">
        <div class="max-w-4xl mx-auto py-6 px-4">

            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold">👥 Do'stlar</h1>
                    <div class="flex gap-2">
                        <Link :href="route('student.friends.followers')" class="btn-secondary">
                        Followers ({{ stats.followers }})
                        </Link>
                        <Link :href="route('student.friends.following')" class="btn-secondary">
                        Following ({{ stats.following }})
                        </Link>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">📊 Faoliyat Lentasi</h2>

                <div v-if="activities.length === 0" class="py-12 text-center text-gray-500">
                    Faoliyat yo'q. Boshqa o'quvchilarni kuzating!
                </div>

                <div v-else class="space-y-4">
                    <div v-for="activity in activities" :key="activity.id"
                        class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                        <img :src="activity.user.avatar || '/default-avatar.png'" class="w-10 h-10 rounded-full"
                            alt="Avatar">
                        <div class="flex-1">
                            <p class="font-semibold">
                                {{ activity.user.first_name }} {{ activity.user.last_name }}
                            </p>
                            <p class="text-sm text-gray-700">{{ activity.icon }} {{ activity.title }}</p>
                            <p v-if="activity.description" class="text-sm text-gray-600">{{ activity.description }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(activity.occurred_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    activities: Array,
    stats: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('uz-UZ');
};
</script>
