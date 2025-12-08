<template>
    <button @click="$emit('select', level)" :disabled="!level.is_unlocked" :class="[
        'relative overflow-hidden rounded-xl transition-all duration-300 group',
        'flex flex-col items-center text-center w-[160px] p-5',
        cardClasses
    ]">
        <!-- Base Gradient Background -->
        <div :class="[
            'absolute inset-0 transition-all duration-300',
            gradientClass,
            !level.is_unlocked ? 'opacity-40' : ''
        ]"></div>

        <!-- Decorative Elements -->
        <div class="absolute inset-0 overflow-hidden" v-if="level.is_unlocked">
            <!-- Top right circle -->
            <div class="absolute -top-6 -right-6 w-20 h-20 rounded-full bg-white/10 blur-sm"></div>
            <!-- Bottom left circle -->
            <div class="absolute -bottom-8 -left-8 w-24 h-24 rounded-full bg-white/10 blur-sm"></div>
            <!-- Shine effect on hover -->
            <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/10 to-transparent 
                        opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <!-- Lock overlay for locked levels -->
        <div v-if="!level.is_unlocked" class="absolute inset-0 flex flex-col items-center justify-center z-10 rounded-2xl
                   bg-gray-900/50 backdrop-blur-[2px]">
            <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center mb-2
                        ring-2 ring-white/20">
                <svg class="w-6 h-6 text-white/60" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <span class="text-sm text-white/50 font-medium">Yopiq</span>
        </div>

        <!-- Content -->
        <div :class="['relative z-[5] flex flex-col items-center w-full', !level.is_unlocked ? 'opacity-60' : '']">
            <!-- Level icon -->
            <div :class="[
                'w-14 h-14 rounded-lg flex items-center justify-center text-3xl mb-3',
                'bg-white/20 backdrop-blur-sm',
                isSelected ? 'ring-2 ring-white shadow-lg scale-110' : 'group-hover:scale-105',
                'transition-all duration-300'
            ]">
                {{ getLevelEmoji(level.code) }}
            </div>

            <!-- Level code -->
            <span class="text-2xl font-bold text-white drop-shadow-md">
                {{ level.code }}
            </span>

            <!-- Level name -->
            <span class="text-sm text-white/80 font-medium mb-2 leading-tight">
                {{ level.name }}
            </span>

            <!-- Progress section - only for unlocked -->
            <div v-if="level.is_unlocked" class="w-full space-y-1">
                <!-- Progress bar -->
                <div class="h-1.5 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-white rounded-full transition-all duration-500"
                        :style="{ width: `${level.progress_percent || 0}%` }"></div>
                </div>
                <!-- Progress text -->
                <div class="flex items-center justify-center gap-1 text-[10px] text-white/70">
                    <span>{{ level.completed_lessons || 0 }}/{{ level.total_lessons || 0 }}</span>
                    <span>dars</span>
                </div>
            </div>

            <!-- Locked level indicator -->
            <div v-else class="w-full mt-2">
                <span class="text-[10px] text-white/40">{{ level.code }} daraja</span>
            </div>
        </div>

        <!-- Selection glow effect -->
        <div v-if="isSelected" class="absolute inset-0 rounded-2xl ring-2 ring-white/80 ring-offset-2 ring-offset-white/20
                   shadow-[0_0_20px_rgba(255,255,255,0.3)]">
        </div>

        <!-- Completed badge -->
        <div v-if="level.progress_percent >= 100" class="absolute -top-1 -right-1 z-20 flex items-center justify-center w-7 h-7 
                   rounded-full bg-yellow-400 shadow-lg ring-2 ring-white">
            <span class="text-sm">⭐</span>
        </div>

        <!-- Selection indicator dot -->
        <div v-if="isSelected" class="absolute -bottom-2 left-1/2 -translate-x-1/2 z-20">
            <div class="w-2 h-2 bg-white rounded-full shadow-lg"></div>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    level: {
        type: Object,
        required: true
    },
    isSelected: {
        type: Boolean,
        default: false
    }
})

defineEmits(['select'])

// Level emoji based on code
const getLevelEmoji = (code) => {
    const emojis = {
        'A1': '🌱',
        'A2': '🌿',
        'A2+': '🌊',
        'B1': '💜',
        'B2': '🔥',
        'C1': '🏔️',
        'C2': '👑'
    }
    return emojis[code] || '📚'
}

// Beautiful gradient configurations
const gradientConfig = {
    A1: 'bg-gradient-to-br from-emerald-400 to-teal-600',
    A2: 'bg-gradient-to-br from-blue-400 to-indigo-600',
    'A2+': 'bg-gradient-to-br from-cyan-400 to-blue-600',
    B1: 'bg-gradient-to-br from-violet-400 to-purple-600',
    B2: 'bg-gradient-to-br from-amber-500 to-orange-600',
    C1: 'bg-gradient-to-br from-rose-500 to-red-600',
    C2: 'bg-gradient-to-br from-fuchsia-500 to-pink-600'
}

const gradientClass = computed(() => gradientConfig[props.level.code] || gradientConfig.A1)

const cardClasses = computed(() => {
    if (!props.level.is_unlocked) {
        return 'cursor-not-allowed hover:scale-100'
    }
    if (props.isSelected) {
        return 'scale-105 cursor-pointer'
    }
    return 'hover:scale-[1.03] hover:-translate-y-1 cursor-pointer shadow-lg hover:shadow-xl'
})
</script>
