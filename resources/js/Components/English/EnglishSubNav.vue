<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useEnglishStore } from '@/Stores/englishStore'
import {
    HomeIcon,
    BookOpenIcon,
    PuzzlePieceIcon,
    TrophyIcon,
    UserIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireSolid } from '@heroicons/vue/24/solid'

const store = useEnglishStore()
const page = usePage()

const currentPath = computed(() => page.url)

const navItems = [
    { name: 'Dashboard', href: '/student/english', icon: HomeIcon },
    { name: 'Learn', href: '/student/english/levels', icon: BookOpenIcon },
    { name: 'Games', href: '/student/english/games', icon: PuzzlePieceIcon },
    { name: 'Battle', href: '/student/english/battle', icon: TrophyIcon },
    { name: 'Profile', href: '/student/english/profile', icon: UserIcon },
]

const isActive = (href) => {
    if (href === '/student/english') {
        return currentPath.value === '/student/english'
    }
    return currentPath.value.startsWith(href)
}

// Fetch English profile on mount
store.fetchProfile()
</script>

<template>
    <!-- English Module Sub-Navigation -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 mb-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between py-3">
                <!-- English Branding -->
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🇬🇧</span>
                    <span class="font-bold text-lg text-gray-900 dark:text-white">Ingliz tili</span>
                    <span v-if="store.currentLevel" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs font-bold rounded-full">
                        {{ store.currentLevel?.code || 'A1' }}
                    </span>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-1">
                    <Link v-for="item in navItems" :key="item.name" :href="item.href"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="isActive(item.href)
                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                        <component :is="item.icon" class="w-4 h-4" />
                        {{ item.name }}
                    </Link>
                </nav>

                <!-- Stats -->
                <div class="flex items-center gap-3">
                    <!-- Streak -->
                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <FireSolid class="w-4 h-4 text-orange-500" />
                        <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ store.streak }}</span>
                    </div>
                    <!-- XP -->
                    <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <span class="text-purple-500">⭐</span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ store.totalXp }} XP</span>
                    </div>
                    <!-- ELO -->
                    <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <span>🏆</span>
                        <span class="text-sm font-bold text-yellow-600 dark:text-yellow-400">{{ store.eloRating }}</span>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <nav class="md:hidden flex items-center gap-2 pb-3 overflow-x-auto">
                <Link v-for="item in navItems" :key="item.name" :href="item.href"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                    :class="isActive(item.href)
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                        : 'text-gray-600 bg-gray-100 dark:text-gray-300 dark:bg-gray-700'">
                    <component :is="item.icon" class="w-3.5 h-3.5" />
                    {{ item.name }}
                </Link>
            </nav>
        </div>
    </div>
</template>
