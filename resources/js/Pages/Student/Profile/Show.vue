<template>
    <StudentLayout title="Profil">
        <div class="max-w-5xl mx-auto py-6 px-4">

            <!-- Cover -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-t-xl h-32"></div>

            <!-- Profile Header -->
            <div class="bg-white rounded-b-xl shadow-md p-6 -mt-16 relative">
                <div class="flex items-start gap-6">
                    <img :src="user.avatar || '/default-avatar.png'"
                        class="w-24 h-24 rounded-full border-4 border-white shadow-lg" alt="Avatar">

                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold">{{ user.first_name }} {{ user.last_name }}</h1>
                                <p v-if="user.username" class="text-gray-600">@{{ user.username }}</p>
                            </div>

                            <div v-if="isOwnProfile">
                                <Link :href="route('student.profile.edit')" class="btn-primary">
                                ✏️ Tahrirlash
                                </Link>
                            </div>
                            <div v-else>
                                <button @click="toggleFollow" :class="isFollowing ? 'btn-secondary' : 'btn-primary'">
                                    {{ isFollowing ? 'Kuzatish bekor' : '+ Kuzatish' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="user.bio" class="mt-3 text-gray-700">
                            {{ user.bio }}
                        </div>

                        <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
                            <span v-if="user.location">📍 {{ user.location }}</span>
                            <a v-if="user.website" :href="user.website" target="_blank"
                                class="text-blue-600 hover:underline">
                                🔗 {{ user.website }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ stats.level || profile?.level || 1 }}</div>
                        <div class="text-xs text-gray-600">Daraja</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.xp?.toLocaleString() ||
                            profile?.xp?.toLocaleString() || 0 }}</div>
                        <div class="text-xs text-gray-600">XP</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.followers || 0 }}</div>
                        <div class="text-xs text-gray-600">Followers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.following || 0 }}</div>
                        <div class="text-xs text-gray-600">Following</div>
                    </div>
                </div>
            </div>

            <!-- Activities -->
            <div v-if="activities && activities.length" class="bg-white rounded-xl shadow-md p-6 mt-6">
                <h2 class="text-xl font-bold mb-4">Faoliyat</h2>
                <div class="space-y-3">
                    <div v-for="activity in activities" :key="activity.id"
                        class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl">{{ activity.icon }}</div>
                        <div class="flex-1">
                            <p class="font-semibold">{{ activity.title }}</p>
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
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    profile: Object,
    stats: Object,
    activities: Array,
    isOwnProfile: Boolean,
    isFollowing: Boolean,
});

const toggleFollow = () => {
    if (props.isFollowing) {
        router.delete(route('student.friends.unfollow', props.user.id));
    } else {
        router.post(route('student.friends.follow', props.user.id));
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uz-UZ');
};
</script>
