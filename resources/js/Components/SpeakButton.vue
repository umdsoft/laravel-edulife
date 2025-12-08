<script setup>
import { onMounted } from 'vue'
import { useTTS } from '@/Composables/useTTS'

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
        validator: (v) => ['sm', 'md', 'lg'].includes(v)
    },
    slow: {
        type: Boolean,
        default: false
    },
    autoPlay: {
        type: Boolean,
        default: false
    },
    label: {
        type: String,
        default: ''
    }
})

const { speak, speakSlowly, isSpeaking, isSupported } = useTTS()

const handleSpeak = async () => {
    if (isSpeaking.value) return
    
    if (props.slow) {
        await speakSlowly(props.text)
    } else {
        await speak(props.text)
    }
}

// Avtomatik ijro
onMounted(() => {
    if (props.autoPlay && isSupported.value) {
        setTimeout(() => handleSpeak(), 500)
    }
})

const sizeClasses = {
    sm: 'w-8 h-8 text-base',
    md: 'w-10 h-10 text-lg',
    lg: 'w-12 h-12 text-xl'
}
</script>

<template>
    <button
        v-if="isSupported"
        @click.stop="handleSpeak"
        :disabled="isSpeaking"
        :class="[
            'rounded-full flex items-center justify-center transition-all shrink-0',
            'hover:bg-indigo-100 dark:hover:bg-indigo-900/30',
            'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
            'disabled:cursor-not-allowed',
            isSpeaking 
                ? 'bg-indigo-100 dark:bg-indigo-900/30 animate-pulse' 
                : 'bg-gray-100 dark:bg-gray-700 hover:scale-105',
            sizeClasses[size]
        ]"
        :title="slow ? 'Sekin tinglash' : 'Tinglash'"
    >
        <span v-if="isSpeaking" class="animate-pulse">🔊</span>
        <span v-else>{{ slow ? '🐢' : '🔈' }}</span>
    </button>
    
    <!-- Optional label -->
    <span v-if="label && isSupported" class="text-sm text-gray-500 ml-1">
        {{ label }}
    </span>
</template>
