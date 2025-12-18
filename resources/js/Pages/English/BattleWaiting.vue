<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import {
    ClockIcon,
    UserGroupIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    XMarkIcon,
    ArrowLeftIcon,
    BoltIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    battle: Object,
    host: Object,
    canJoin: Boolean,
})

const copied = ref(false)
const isJoining = ref(false)
const waitTime = ref(0)
const waitInterval = ref(null)
const error = ref('')

const copyCode = async () => {
    if (!props.battle?.code) return

    try {
        await navigator.clipboard.writeText(props.battle.code)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}

const joinBattle = async () => {
    if (isJoining.value) return

    isJoining.value = true
    error.value = ''

    try {
        const response = await axios.post('/api/v1/english/battles/join', {
            code: props.battle.code
        })

        if (response.data.success) {
            // Redirect to battle arena
            router.visit(`/student/english/battle/${props.battle.id}`)
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Battle\'ga qo\'shilishda xatolik'
        isJoining.value = false
    }
}

const goBack = () => {
    router.visit('/student/english/battle')
}

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, '0')}`
}

onMounted(() => {
    // Start wait timer
    waitInterval.value = setInterval(() => {
        waitTime.value++
    }, 1000)
})

onUnmounted(() => {
    if (waitInterval.value) {
        clearInterval(waitInterval.value)
    }
})
</script>

<template>
    <Head title="Battle Kutish Xonasi" />

    <div class="min-h-screen bg-gradient-to-b from-purple-900 via-purple-800 to-indigo-900 text-white flex flex-col">
        <!-- Header -->
        <header class="p-4 flex items-center justify-between">
            <button @click="goBack" class="flex items-center gap-2 text-purple-300 hover:text-white transition-colors">
                <ArrowLeftIcon class="w-5 h-5" />
                <span>Orqaga</span>
            </button>
            <div class="flex items-center gap-2 text-purple-300">
                <ClockIcon class="w-5 h-5" />
                <span>{{ formatTime(waitTime) }}</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col items-center justify-center p-6 -mt-20">
            <!-- Waiting Animation -->
            <div class="relative mb-8">
                <div class="w-32 h-32 rounded-full bg-purple-500/20 animate-ping absolute inset-0"></div>
                <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center relative">
                    <UserGroupIcon class="w-16 h-16 text-white" />
                </div>
            </div>

            <h1 class="text-3xl font-bold mb-2 text-center">Raqib Kutilmoqda</h1>
            <p class="text-purple-300 text-center mb-8">
                Do'stingizga battle kodini yuboring yoki raqib topilguncha kuting
            </p>

            <!-- Battle Info Card -->
            <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-6 w-full max-w-md mb-6 border border-white/10">
                <!-- Battle Name -->
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-white">{{ battle?.name || 'Battle' }}</h2>
                    <div class="flex items-center justify-center gap-2 mt-2">
                        <span class="px-3 py-1 bg-purple-500/30 rounded-full text-sm text-purple-200 capitalize">
                            {{ battle?.battle_type }}
                        </span>
                        <span class="px-3 py-1 bg-blue-500/30 rounded-full text-sm text-blue-200">
                            {{ battle?.settings?.rounds || 10 }} raund
                        </span>
                    </div>
                </div>

                <!-- Battle Code -->
                <div class="bg-white/10 rounded-2xl p-4 mb-6">
                    <p class="text-sm text-purple-300 text-center mb-2">Battle Kodi</p>
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-4xl font-black tracking-[0.3em] text-white font-mono">
                            {{ battle?.code }}
                        </span>
                        <button
                            @click="copyCode"
                            class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors"
                            :class="{ 'bg-green-500/30': copied }"
                        >
                            <CheckIcon v-if="copied" class="w-6 h-6 text-green-400" />
                            <ClipboardDocumentIcon v-else class="w-6 h-6" />
                        </button>
                    </div>
                    <p v-if="copied" class="text-green-400 text-sm text-center mt-2">Nusxalandi!</p>
                </div>

                <!-- Host Info -->
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-lg font-bold">
                            {{ host?.name?.[0]?.toUpperCase() || 'H' }}
                        </div>
                        <div>
                            <p class="font-medium text-white">{{ host?.name || 'Host' }}</p>
                            <p class="text-sm text-purple-300">{{ host?.elo_rating || 1000 }} ELO</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-yellow-500/20 rounded-full text-yellow-400 text-sm font-medium">
                        Host
                    </span>
                </div>
            </div>

            <!-- Join Button (for non-host) -->
            <div v-if="canJoin" class="w-full max-w-md">
                <p v-if="error" class="text-red-400 text-center mb-4">{{ error }}</p>

                <button
                    @click="joinBattle"
                    :disabled="isJoining"
                    class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-lg font-bold rounded-2xl hover:from-green-600 hover:to-emerald-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <BoltIcon v-if="!isJoining" class="w-6 h-6" />
                    <span v-if="isJoining">Qo'shilmoqda...</span>
                    <span v-else>Battle'ga Qo'shilish</span>
                </button>
            </div>

            <!-- Waiting dots animation -->
            <div v-else class="flex items-center gap-2 mt-4">
                <div class="w-3 h-3 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                <div class="w-3 h-3 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                <div class="w-3 h-3 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
            </div>
        </main>

        <!-- Tips -->
        <footer class="p-6">
            <div class="max-w-md mx-auto bg-white/5 rounded-2xl p-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                        <TrophyIcon class="w-5 h-5 text-yellow-400" />
                    </div>
                    <div>
                        <p class="font-medium text-white mb-1">Pro Tip</p>
                        <p class="text-sm text-purple-300">
                            Tez javob bersangiz, ko'proq ball olasiz! Har soniya uchun bonus ball beriladi.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
