<script setup>
defineProps({
    player: Object,
    score: Number,
    isCurrentUser: Boolean,
});
</script>

<template>
    <div class="flex items-center gap-4 p-4 bg-white rounded-xl border-2 transition-colors"
        :class="isCurrentUser ? 'border-purple-500 bg-purple-50' : 'border-gray-200'">
        <img :src="player.avatar_url || `https://ui-avatars.com/api/?name=${player.first_name}+${player.last_name}`"
            class="w-12 h-12 rounded-full border-2 border-white shadow-sm">

        <div class="flex-1">
            <h3 class="font-bold text-gray-900 line-clamp-1">{{ player.full_name }}</h3>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-yellow-600 font-bold flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    {{ score }}
                </span>
                <span class="text-gray-400">|</span>
                <span class="text-gray-500">Lvl {{ player.student_profile?.level || 1 }}</span>
            </div>
        </div>
    </div>
</template>
