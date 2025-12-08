<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useEnglishStore } from '@/Stores/englishStore'
import {
    HomeIcon,
    BookOpenIcon,
    PuzzlePieceIcon,
    TrophyIcon,
    UserIcon,
    BellIcon,
    Bars3Icon,
    XMarkIcon,
    MoonIcon,
    SunIcon,
    FireIcon,
    SparklesIcon,
    CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    title: { type: String, default: 'English' }
})

const store = useEnglishStore()
const page = usePage()

const isMobileMenuOpen = ref(false)
const isDarkMode = ref(false)

const navItems = [
    { name: 'Dashboard', href: '/student/english', icon: HomeIcon },
    { name: 'Learn', href: '/student/english/levels', icon: BookOpenIcon },
    { name: 'Games', href: '/student/english/games', icon: PuzzlePieceIcon },
    { name: 'Battle', href: '/student/english/battle', icon: TrophyIcon },
    { name: 'Profile', href: '/student/english/profile', icon: UserIcon },
]

const bottomNavItems = [
    { name: 'Home', href: '/student/english', icon: HomeIcon },
    { name: 'Learn', href: '/student/english/levels', icon: BookOpenIcon },
    { name: 'Battle', href: '/student/english/battle', icon: TrophyIcon },
    { name: 'Games', href: '/student/english/games', icon: PuzzlePieceIcon },
    { name: 'Profile', href: '/student/english/profile', icon: UserIcon },
]

const currentPath = computed(() => page.url)

const isActive = (href) => {
    if (href === '/student/english') {
        return currentPath.value === '/student/english'
    }
    return currentPath.value.startsWith(href)
}

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value
    document.documentElement.classList.toggle('dark', isDarkMode.value)
    localStorage.setItem('darkMode', isDarkMode.value)
}

onMounted(() => {
    const savedDarkMode = localStorage.getItem('darkMode')
    if (savedDarkMode === 'true') {
        isDarkMode.value = true
        document.documentElement.classList.add('dark')
    }
    
    store.fetchProfile()
    store.fetchNotifications()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Navigation Bar -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <Link href="/student/english" class="flex items-center space-x-2">
                        <span class="text-2xl">🇬🇧</span>
                        <span class="font-bold text-xl text-gray-900 dark:text-white hidden sm:block">
                            EDULIFE English
                        </span>
                    </Link>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.name"
                            :href="item.href"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="isActive(item.href) 
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                                : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
                        >
                            {{ item.name }}
                        </Link>
                    </div>
                    
                    <!-- User Stats & Actions -->
                    <div class="flex items-center space-x-3">
                        <!-- Streak -->
                        <div class="flex items-center space-x-1 px-2 py-1 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <FireSolid class="w-5 h-5 text-orange-500" />
                            <span class="text-sm font-bold text-orange-600 dark:text-orange-400">
                                {{ store.streak }}
                            </span>
                        </div>
                        
                        <!-- XP -->
                        <div class="hidden sm:flex items-center space-x-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <SparklesIcon class="w-5 h-5 text-purple-500" />
                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ store.totalXp }}
                            </span>
                        </div>
                        
                        <!-- Coins -->
                        <div class="hidden sm:flex items-center space-x-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <CurrencyDollarIcon class="w-5 h-5 text-yellow-500" />
                            <span class="text-sm font-bold text-yellow-600 dark:text-yellow-400">
                                {{ store.coins }}
                            </span>
                        </div>
                        
                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <MoonIcon v-if="!isDarkMode" class="w-5 h-5" />
                            <SunIcon v-else class="w-5 h-5 text-yellow-400" />
                        </button>
                        
                        <!-- Notifications -->
                        <button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <BellIcon class="w-5 h-5" />
                            <span
                                v-if="store.unreadNotifications > 0"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                            >
                                {{ store.unreadNotifications }}
                            </span>
                        </button>
                        
                        <!-- Profile Avatar -->
                        <Link href="/student/english/profile" class="hidden sm:block">
                            <img
                                :src="store.profile?.avatar || '/images/default-avatar.png'"
                                class="w-8 h-8 rounded-full border-2 border-gray-200 dark:border-gray-600"
                                alt="Profile"
                            />
                        </Link>
                        
                        <!-- Mobile Menu Button -->
                        <button
                            @click="isMobileMenuOpen = !isMobileMenuOpen"
                            class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <Bars3Icon v-if="!isMobileMenuOpen" class="w-6 h-6" />
                            <XMarkIcon v-else class="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div
                v-show="isMobileMenuOpen"
                class="md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700"
            >
                <div class="px-4 py-3 space-y-2">
                    <div class="flex justify-around py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <FireSolid class="w-5 h-5 text-orange-500" />
                                <span class="font-bold text-gray-900 dark:text-white">{{ store.streak }}</span>
                            </div>
                            <span class="text-xs text-gray-500">Streak</span>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <SparklesIcon class="w-5 h-5 text-purple-500" />
                                <span class="font-bold text-gray-900 dark:text-white">{{ store.totalXp }}</span>
                            </div>
                            <span class="text-xs text-gray-500">XP</span>
                        </div>
                        <div class="text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <CurrencyDollarIcon class="w-5 h-5 text-yellow-500" />
                                <span class="font-bold text-gray-900 dark:text-white">{{ store.coins }}</span>
                            </div>
                            <span class="text-xs text-gray-500">Coins</span>
                        </div>
                    </div>
                    
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        @click="isMobileMenuOpen = false"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg"
                        :class="isActive(item.href)
                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="pt-16 pb-20 md:pb-8">
            <slot />
        </main>
        
        <!-- Mobile Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 safe-area-bottom">
            <div class="flex justify-around items-center h-16">
                <Link
                    v-for="item in bottomNavItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex flex-col items-center justify-center w-full h-full space-y-1"
                    :class="isActive(item.href)
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-gray-500 dark:text-gray-400'"
                >
                    <component :is="item.icon" class="w-6 h-6" />
                    <span class="text-xs">{{ item.name }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
