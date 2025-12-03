<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const studentProfile = computed(() => page.props.auth.studentProfile);

const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);

const navigation = [
    { name: 'Kurslar', href: route('student.courses.index'), icon: 'book-open' },
    { name: 'Battle', href: '#', icon: 'lightning-bolt' }, // To be implemented
    { name: 'Missions', href: '#', icon: 'target' }, // To be implemented
    { name: 'Turnirlar', href: '#', icon: 'trophy' }, // To be implemented
];

const bottomNavigation = [
    { name: 'Bosh', href: route('student.dashboard'), icon: 'home' },
    { name: 'Kurslar', href: route('student.courses.index'), icon: 'book-open' },
    { name: 'Battle', href: '#', icon: 'lightning-bolt' },
    { name: 'Vazifalar', href: '#', icon: 'target' },
    { name: 'Profil', href: '#', icon: 'user' }, // To be implemented
];

const isActive = (href) => {
    return href !== '#' && page.url.startsWith(new URL(href).pathname);
};

const logout = () => {
    router.post('/logout');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num || 0);
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 h-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
                <div class="flex items-center justify-between h-full">
                    <!-- Left: Logo & Search -->
                    <div class="flex items-center gap-8">
                        <Link :href="route('student.dashboard')" class="flex items-center gap-2">
                        <span class="text-2xl">🎓</span>
                        <span class="text-xl font-bold text-gray-900 hidden sm:inline-block">EDULIFE</span>
                        </Link>

                        <!-- Search (Desktop) -->
                        <div class="hidden md:block relative w-64">
                            <input type="text" placeholder="Kurslarni izlash..."
                                class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm"
                                @keydown.enter="router.get(route('student.search.index'), { q: $event.target.value })">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Center: Navigation (Desktop) -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link v-for="item in navigation" :key="item.name" :href="item.href" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            isActive(item.href) ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                        {{ item.name }}
                        </Link>
                    </nav>

                    <!-- Right: Gamification & Profile -->
                    <div class="flex items-center gap-4">
                        <!-- Gamification Badges (Hidden on mobile) -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- XP/Level -->
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg" title="Level & XP">
                                <span class="text-yellow-500">⭐</span>
                                <div class="flex flex-col leading-none">
                                    <span class="text-xs font-bold text-gray-900">Lvl {{ studentProfile?.level || 1
                                        }}</span>
                                    <span class="text-[10px] text-gray-500">{{ formatNumber(studentProfile?.xp) }}
                                        XP</span>
                                </div>
                            </div>

                            <!-- Coins -->
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-yellow-50 rounded-lg" title="Coins">
                                <span class="text-yellow-600">🪙</span>
                                <span class="text-sm font-bold text-yellow-700">{{ formatNumber(studentProfile?.coins)
                                    }}</span>
                            </div>

                            <!-- Streak -->
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-orange-50 rounded-lg"
                                title="Daily Streak">
                                <span class="text-orange-500">🔥</span>
                                <span class="text-sm font-bold text-orange-600">{{ studentProfile?.streak_days || 0
                                    }}</span>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="$page.props.auth.unread_notifications_count > 0"
                                class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>

                        <!-- User Menu -->
                        <div class="relative">
                            <button @click="profileDropdownOpen = !profileDropdownOpen"
                                class="flex items-center gap-2 focus:outline-none">
                                <img :src="user?.avatar ? `/storage/${user.avatar}` : `https://ui-avatars.com/api/?name=${user?.first_name}+${user?.last_name}&background=7C3AED&color=fff`"
                                    alt="User"
                                    class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm">
                            </button>

                            <!-- Dropdown -->
                            <div v-if="profileDropdownOpen"
                                class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50"
                                @click.away="profileDropdownOpen = false">

                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-bold text-gray-900">{{ user?.full_name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ user?.email }}</p>

                                    <!-- Level Progress -->
                                    <div class="mt-3">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="text-gray-500">Level {{ studentProfile?.level || 1 }}</span>
                                            <span class="text-purple-600 font-medium">{{ studentProfile?.level_progress
                                                || 0 }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                                            <div class="bg-purple-500 h-1.5 rounded-full transition-all duration-500"
                                                :style="{ width: `${studentProfile?.level_progress || 0}%` }"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="py-1">
                                    <Link :href="route('student.dashboard')"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                    <span>🏠</span> Dashboard
                                    </Link>
                                    <Link :href="route('student.my-courses.index')"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                    <span>📚</span> Mening kurslarim
                                    </Link>
                                    <Link :href="route('student.wishlist.index')"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                    <span>❤️</span> Istaklar ro'yxati
                                    </Link>
                                    <a href="#"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <span>🏆</span> Yutuqlarim
                                    </a>
                                    <a href="#"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700">
                                        <span>⚙️</span> Sozlamalar
                                    </a>
                                </div>

                                <div class="border-t border-gray-100 py-1">
                                    <button @click="logout"
                                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <span>🚪</span> Chiqish
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 lg:pb-8">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 lg:hidden z-40 pb-safe">
            <div class="flex justify-around items-center h-16">
                <Link v-for="item in bottomNavigation" :key="item.name" :href="item.href" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1',
                    isActive(item.href) ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                <svg v-if="item.icon === 'home'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <svg v-else-if="item.icon === 'book-open'" class="w-6 h-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <svg v-else-if="item.icon === 'lightning-bolt'" class="w-6 h-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg v-else-if="item.icon === 'target'" class="w-6 h-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg v-else-if="item.icon === 'user'" class="w-6 h-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-[10px] font-medium">{{ item.name }}</span>
                </Link>
            </div>
        </div>

        <!-- Backdrop for dropdown -->
        <div v-if="profileDropdownOpen" class="fixed inset-0 z-40 bg-transparent" @click="profileDropdownOpen = false">
        </div>
    </div>
</template>

<style>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
