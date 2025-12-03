<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';

defineProps({
    user: Object,
});
</script>

<template>

    <Head title="Profil" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Profil</h2>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Card -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                    <div class="relative">
                        <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.full_name"
                            class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg" />
                        <div v-else
                            class="w-24 h-24 rounded-full bg-emerald-100 flex items-center justify-center text-3xl font-bold text-emerald-600 border-4 border-white shadow-lg">
                            {{ user.first_name[0] }}{{ user.last_name[0] }}
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ user.full_name }}</h1>
                                <p class="text-gray-500">{{ user.teacher_profile?.headline || 'O\'qituvchi' }}</p>
                            </div>
                            <Link href="/teacher/profile/edit"
                                class="px-4 py-2 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-xl hover:bg-emerald-100 transition-colors">
                            Tahrirlash
                            </Link>
                        </div>

                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-1">
                                <span class="text-emerald-600">📧</span>
                                {{ user.email }}
                            </div>
                            <div class="flex items-center gap-1" v-if="user.phone">
                                <span class="text-emerald-600">📱</span>
                                {{ user.phone }}
                            </div>
                            <div class="flex items-center gap-1" v-if="user.profile?.website">
                                <span class="text-emerald-600">🌐</span>
                                <a :href="user.profile.website" target="_blank" class="hover:underline">{{
                                    user.profile.website
                                    }}</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 pt-8 border-t border-gray-100">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ user.teacher_profile?.level || 'New' }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Daraja</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ user.teacher_profile?.commission_rate || 30 }}%
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Komissiya</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ user.teacher_profile?.experience_years || 0 }}
                            yil
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Tajriba</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">⭐ {{ user.teacher_profile?.avg_rating || '0.0' }}
                        </div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider">Reyting</div>
                    </div>
                </div>
            </div>

            <!-- Bio -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Bio</h3>
                <p class="text-gray-600 whitespace-pre-line">
                    {{ user.profile?.bio || 'Ma\'lumot kiritilmagan' }}
                </p>
            </div>

            <!-- Expertise -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Mutaxassislik</h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(skill, index) in user.teacher_profile?.expertise || []" :key="index"
                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm">
                        {{ skill }}
                    </span>
                    <span v-if="!user.teacher_profile?.expertise?.length" class="text-gray-500 italic">
                        Kiritilmagan
                    </span>
                </div>
            </div>

            <!-- Social Links -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Ijtimoiy tarmoqlar</h3>
                <div class="space-y-3">
                    <div v-for="(link, platform) in user.profile?.social_links || {}" :key="platform"
                        class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 capitalize">
                            {{ platform[0] }}
                        </div>
                        <a :href="link" target="_blank" class="text-emerald-600 hover:underline">{{ link }}</a>
                    </div>
                    <p v-if="!Object.keys(user.profile?.social_links || {}).length" class="text-gray-500 italic">
                        Kiritilmagan
                    </p>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
