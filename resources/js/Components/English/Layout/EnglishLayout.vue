<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useEnglishStore } from '@/Stores/englishStore'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
    HomeIcon,
    BookOpenIcon,
    PuzzlePieceIcon,
    TrophyIcon,
    UserIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    title: { type: String, default: 'English' }
})

const store = useEnglishStore()
const page = usePage()

const navItems = [
    { name: 'Dashboard', href: '/student/english', icon: HomeIcon },
    { name: 'Learn', href: '/student/english/levels', icon: BookOpenIcon },
    { name: 'Games', href: '/student/english/games', icon: PuzzlePieceIcon },
    { name: 'Battle', href: '/student/english/battle', icon: TrophyIcon },
    { name: 'Profile', href: '/student/english/profile', icon: UserIcon },
]

const currentPath = computed(() => page.url)

const isActive = (href) => {
    if (href === '/student/english') {
        return currentPath.value === '/student/english'
    }
    return currentPath.value.startsWith(href)
}

onMounted(() => {
    store.fetchProfile()
    store.fetchNotifications()
})
</script>

<template>
    <StudentLayout>
        <!-- English Module Sub-Navigation -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-1 overflow-x-auto">
            <div class="flex space-x-1 min-w-max">
                <Link v-for="item in navItems" :key="item.name" :href="item.href"
                    class="flex items-center space-x-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="isActive(item.href)
                        ? 'bg-blue-50 text-blue-600 shadow-sm'
                        : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50'">
                <component :is="item.icon" class="w-5 h-5" :class="isActive(item.href) ? 'text-blue-500' : 'text-gray-400'" />
                <span>{{ item.name }}</span>
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <slot />
    </StudentLayout>
</template>
