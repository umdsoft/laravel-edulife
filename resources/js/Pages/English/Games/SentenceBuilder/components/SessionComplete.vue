<script setup>
import { defineProps, defineEmits, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    show: Boolean,
    result: Object,
    level: Object
})

const emit = defineEmits(['close', 'restart', 'next-level'])

const percentage = computed(() => props.result?.accuracy || 0)

const getMessage = () => {
    if (percentage.value === 100) return 'Mukammal!'
    if (percentage.value >= 80) return 'Ajoyib natija!'
    if (percentage.value >= 60) return 'Yaxshi!'
    return 'Harakat qiling!'
}

const getStars = () => {
    return props.result?.stars || 0
}
</script>

<template>
    <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100" leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center px-4 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="$emit('close')"></div>

            <!-- Modal -->
            <div
                class="relative bg-[#1e293b] rounded-[2rem] shadow-2xl w-full max-w-md border border-white/10 overflow-hidden transform transition-all">
                <!-- Confetti/Celebration effect placeholder -->
                <div v-if="percentage >= 80" class="absolute inset-0 pointer-events-none">
                    <div class="absolute top-0 left-1/4 w-32 h-32 bg-blue-500/20 rounded-full blur-3xl animate-pulse">
                    </div>
                    <div
                        class="absolute bottom-0 right-1/4 w-32 h-32 bg-purple-500/20 rounded-full blur-3xl animate-pulse">
                    </div>
                </div>

                <div class="p-8 text-center relative z-10">
                    <!-- Award Icon -->
                    <div class="mb-6 relative">
                        <div class="text-8xl animate-bounce duration-1000">
                            {{ percentage >= 90 ? '🏆' : (percentage >= 60 ? '⭐' : '📝') }}
                        </div>
                    </div>

                    <!-- Title -->
                    <h2 class="text-3xl font-black text-white mb-2">
                        {{ getMessage() }}
                    </h2>
                    <p class="text-white/50 mb-8">Siz {{ result.sentences_completed }} ta gapni to'g'ri topdingiz</p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div class="text-xs text-white/40 uppercase font-bold tracking-wider mb-1">Aniqlik</div>
                            <div class="text-2xl font-black text-blue-400">{{ result.accuracy }}%</div>
                        </div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div class="text-xs text-white/40 uppercase font-bold tracking-wider mb-1">Yulduzlar</div>
                            <div class="flex items-center justify-center gap-1 text-2xl">
                                <span :class="getStars() >= 1 ? 'text-yellow-400' : 'text-gray-600'">★</span>
                                <span :class="getStars() >= 2 ? 'text-yellow-400' : 'text-gray-600'">★</span>
                                <span :class="getStars() >= 3 ? 'text-yellow-400' : 'text-gray-600'">★</span>
                            </div>
                        </div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div class="text-xs text-white/40 uppercase font-bold tracking-wider mb-1">Ballar</div>
                            <div class="text-2xl font-black text-purple-400">{{ result.total_score }}</div>
                        </div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                            <div class="text-xs text-white/40 uppercase font-bold tracking-wider mb-1">Mukofot</div>
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-sm font-bold text-yellow-500">+{{ result.rewards?.xp_completion || 0
                                    }} XP</span>
                            </div>
                        </div>
                    </div>

                    <!-- Level Complete Badge (if first time) -->
                    <div v-if="result.rewards?.coins_completion"
                        class="mb-8 p-3 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl border border-yellow-500/30 flex items-center justify-center gap-3">
                        <span class="text-2xl">🎁</span>
                        <div class="text-left">
                            <div class="text-yellow-300 font-bold text-sm">Daraja yakunlandi!</div>
                            <div class="text-yellow-200/60 text-xs">+{{ result.rewards.coins_completion }} tanga
                                qo'shildi</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-3">
                        <button @click="$emit('close')"
                            class="w-full py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-blue-500/25 transition-all active:scale-95">
                            Davom etish
                        </button>

                        <button @click="$emit('restart')"
                            class="w-full py-4 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold text-lg transition-all active:scale-95">
                            Qayta o'ynash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
