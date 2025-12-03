<script setup>
defineProps({
    announcement: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>
    <div
        class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden">
        <div v-if="announcement.is_pinned"
            class="absolute top-0 right-0 bg-purple-100 text-purple-700 px-3 py-1 rounded-bl-xl text-xs font-bold flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2h3V5H6v16h12V10h-3v-2h3c1.1 0 2 .9 2 2z" />
            </svg>
            MUHIM
        </div>

        <div class="flex items-center gap-3 mb-4">
            <img :src="announcement.teacher.avatar_url || `https://ui-avatars.com/api/?name=${announcement.teacher.first_name}+${announcement.teacher.last_name}`"
                class="w-10 h-10 rounded-full border border-gray-200">
            <div>
                <h4 class="font-bold text-gray-900">{{ announcement.title }}</h4>
                <p class="text-xs text-gray-500">{{ formatDate(announcement.published_at) }}</p>
            </div>
        </div>

        <div class="prose prose-sm max-w-none text-gray-600" v-html="announcement.content"></div>
    </div>
</template>
