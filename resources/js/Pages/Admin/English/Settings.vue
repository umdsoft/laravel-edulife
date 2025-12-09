<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    settings: Array,
    auditLogs: Array,
    isTestMode: Boolean
})

// Test mode toggle
const isToggling = ref(false)

const toggleTestMode = () => {
    isToggling.value = true
    router.post('/admin/english-settings/test-mode', {}, {
        preserveScroll: true,
        onFinish: () => {
            isToggling.value = false
        }
    })
}

// Settings form
const form = useForm({
    settings: props.settings.map(s => ({
        key: s.key,
        value: s.value
    }))
})

const saveSettings = () => {
    form.post('/admin/english-settings/batch', {
        preserveScroll: true
    })
}

// Find setting by key
const getSetting = (key) => {
    return props.settings.find(s => s.key === key)
}

// Update single setting value in form
const updateSettingValue = (key, value) => {
    const index = form.settings.findIndex(s => s.key === key)
    if (index !== -1) {
        form.settings[index].value = value
    }
}

// Format audit log values
const formatValue = (value) => {
    if (value === 'true') return '✅ Yoqiq'
    if (value === 'false') return '❌ O\'chiq'
    return value
}
</script>

<template>
    <AdminLayout>
        <div class="max-w-6xl mx-auto px-4 py-8">
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    ⚙️ Ingliz tili sozlamalari
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Test rejimi va boshqa sozlamalarni boshqaring
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Settings -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Test Mode Card -->
                    <div :class="[
                        'rounded-2xl p-6 shadow-lg transition-all duration-300',
                        isTestMode 
                            ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white'
                            : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'
                    ]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div :class="[
                                    'w-16 h-16 rounded-2xl flex items-center justify-center text-3xl',
                                    isTestMode 
                                        ? 'bg-white/20' 
                                        : 'bg-gray-100 dark:bg-gray-700'
                                ]">
                                    {{ isTestMode ? '🔓' : '🔒' }}
                                </div>
                                <div>
                                    <h2 :class="[
                                        'text-xl font-bold',
                                        isTestMode ? 'text-white' : 'text-gray-900 dark:text-white'
                                    ]">
                                        Test rejimi
                                    </h2>
                                    <p :class="[
                                        'text-sm',
                                        isTestMode ? 'text-white/80' : 'text-gray-500'
                                    ]">
                                        {{ isTestMode 
                                            ? 'Barcha darslar va levellar ochiq' 
                                            : 'Darslar normal holatda (qulflangan)' 
                                        }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Toggle Button -->
                            <button
                                @click="toggleTestMode"
                                :disabled="isToggling"
                                :class="[
                                    'relative inline-flex h-12 w-24 items-center rounded-full transition-colors duration-300',
                                    isTestMode 
                                        ? 'bg-white/30' 
                                        : 'bg-gray-200 dark:bg-gray-600'
                                ]"
                            >
                                <span :class="[
                                    'inline-block h-10 w-10 transform rounded-full bg-white shadow-lg transition-transform duration-300 flex items-center justify-center',
                                    isTestMode ? 'translate-x-12' : 'translate-x-1'
                                ]">
                                    <span v-if="isToggling" class="animate-spin">⏳</span>
                                    <span v-else>{{ isTestMode ? '✓' : '✕' }}</span>
                                </span>
                            </button>
                        </div>
                        
                        <!-- Warning when enabled -->
                        <div 
                            v-if="isTestMode"
                            class="mt-4 p-4 bg-white/10 rounded-xl"
                        >
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div class="text-sm">
                                    <p class="font-semibold">Diqqat!</p>
                                    <p class="text-white/80">
                                        Test rejimi yoqilgan. Barcha foydalanuvchilar barcha darslarga kira oladi. 
                                        Production muhitida o'chirishni unutmang!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                            📚 Dars sozlamalari
                        </h3>
                        
                        <div class="space-y-6">
                            <!-- 80% requirement -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        O'tish uchun 80% talab qilish
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        O'chirilsa foydalanuvchilar istalgan ball bilan o'tadi
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        :checked="getSetting('english_require_80_percent')?.value"
                                        @change="updateSettingValue('english_require_80_percent', $event.target.checked)"
                                        class="sr-only peer"
                                    >
                                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            
                            <!-- Max XP -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                            Dars uchun maksimal XP
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Bitta darsdan olinadigan maksimal XP
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number"
                                            :value="getSetting('english_max_xp_per_lesson')?.value"
                                            @input="updateSettingValue('english_max_xp_per_lesson', parseInt($event.target.value))"
                                            min="1"
                                            max="100"
                                            class="w-20 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center font-semibold text-gray-900 dark:text-white"
                                        >
                                        <span class="text-amber-500 font-semibold">XP</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Max Coins -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                            Dars uchun maksimal Coin
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Bitta darsdan olinadigan maksimal tangalar
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="number"
                                            :value="getSetting('english_max_coins_per_lesson')?.value"
                                            @input="updateSettingValue('english_max_coins_per_lesson', parseInt($event.target.value))"
                                            min="1"
                                            max="50"
                                            class="w-20 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center font-semibold text-gray-900 dark:text-white"
                                        >
                                        <span class="text-yellow-500 font-semibold">💰</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="mt-6 flex justify-end">
                            <button
                                @click="saveSettings"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold transition-colors flex items-center gap-2"
                            >
                                <span v-if="form.processing">⏳</span>
                                <span v-else>💾</span>
                                Saqlash
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Audit Logs Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            📋 So'nggi o'zgarishlar
                        </h3>
                        
                        <div class="space-y-3 max-h-[600px] overflow-y-auto">
                            <div 
                                v-for="log in auditLogs"
                                :key="log.id"
                                class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ log.user }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ log.time_ago }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-mono">{{ log.setting_key }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-sm">
                                    <span class="text-red-500">{{ formatValue(log.old_value) }}</span>
                                    <span>→</span>
                                    <span class="text-emerald-500">{{ formatValue(log.new_value) }}</span>
                                </div>
                            </div>
                            
                            <div v-if="auditLogs.length === 0" class="text-center py-8 text-gray-500">
                                <span class="text-4xl">📝</span>
                                <p class="mt-2">Hali o'zgarish yo'q</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
