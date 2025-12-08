<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const studentProfile = computed(() => page.props.auth.studentProfile);

const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);
const isDarkMode = ref(false);

const isActive = (href) => {
    if (!href || href === '#') return false;
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num || 0);
};

const navigateTo = (path) => {
    router.visit(path);
};

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    document.documentElement.classList.toggle('dark', isDarkMode.value);
    localStorage.setItem('edulife_dark_mode', isDarkMode.value);
};

onMounted(() => {
    const savedDarkMode = localStorage.getItem('edulife_dark_mode');
    if (savedDarkMode === 'true') {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200 h-16">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-full">
                <div class="flex items-center justify-between h-full">
                    <!-- Left: Logo & Search -->
                    <div class="flex items-center gap-8">
                        <a href="/student/dashboard" @click.prevent="navigateTo('/student/dashboard')"
                            class="flex items-center gap-2">
                            <span class="text-2xl">🎓</span>
                            <span class="text-xl font-bold text-gray-900 hidden sm:inline-block">EDULIFE</span>
                        </a>

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
                        <a href="/student/courses" @click.prevent="navigateTo('/student/courses')" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
                            isActive('/student/courses') ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                            Kurslar
                        </a>
                        <a href="/student/english" @click.prevent="navigateTo('/student/english')" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
                            isActive('/student/english') ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                            Ingliz tili
                        </a>
                        <a href="/student/lab" @click.prevent="navigateTo('/student/lab')" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
                            isActive('/student/lab') ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                            Lab
                        </a>
                        <a href="/student/olympiads" @click.prevent="navigateTo('/student/olympiads')" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
                            isActive('/student/olympiads') ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                            Olimpiada
                        </a>
                        <a href="/student/tournaments" @click.prevent="navigateTo('/student/tournaments')" :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
                            isActive('/student/tournaments') ? 'bg-purple-50 text-purple-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                        ]">
                            Turnirlar
                        </a>
                    </nav>

                    <!-- Right: Gamification & Profile -->
                    <div class="flex items-center gap-4">
                        <!-- Gamification Badges (Hidden on mobile) -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- XP/Level -->
                            <a href="/student/xp" @click.prevent="navigateTo('/student/xp')"
                                class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer"
                                title="Level & XP">
                                <span class="text-yellow-500">⭐</span>
                                <div class="flex flex-col leading-none">
                                    <span class="text-xs font-bold text-gray-900">Lvl {{ studentProfile?.level || 1
                                    }}</span>
                                    <span class="text-[10px] text-gray-500">{{ formatNumber(studentProfile?.xp) }}
                                        XP</span>
                                </div>
                            </a>

                            <!-- Coins -->
                            <a href="/student/shop" @click.prevent="navigateTo('/student/shop')"
                                class="flex items-center gap-2 px-3 py-1.5 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors cursor-pointer"
                                title="Do'kon">
                                <span class="text-yellow-600">🪙</span>
                                <span class="text-sm font-bold text-yellow-700">{{ formatNumber(studentProfile?.coins)
                                }}</span>
                            </a>

                            <!-- Streak -->
                            <a href="/student/xp" @click.prevent="navigateTo('/student/xp')"
                                class="flex items-center gap-2 px-3 py-1.5 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors cursor-pointer"
                                title="Daily Streak">
                                <span class="text-orange-500">🔥</span>
                                <span class="text-sm font-bold text-orange-600">{{ studentProfile?.streak_days || 0
                                }}</span>
                            </a>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button @click="toggleDarkMode"
                            class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            :title="isDarkMode ? 'Yorug\' rejim' : 'Tun rejimi'">
                            <svg v-if="!isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <!-- Notifications -->
                        <a href="/student/notifications" @click.prevent="navigateTo('/student/notifications')"
                            class="relative p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors cursor-pointer">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="$page.props.auth.unread_notifications_count > 0"
                                class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        </a>

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
                                class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50">

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
                                    <a href="/student/dashboard"
                                        @click.prevent="navigateTo('/student/dashboard'); profileDropdownOpen = false"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 cursor-pointer">
                                        <span>🏠</span> Dashboard
                                    </a>
                                    <a href="/student/my-courses"
                                        @click.prevent="navigateTo('/student/my-courses'); profileDropdownOpen = false"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 cursor-pointer">
                                        <span>📚</span> Mening kurslarim
                                    </a>
                                    <a href="/student/wishlist"
                                        @click.prevent="navigateTo('/student/wishlist'); profileDropdownOpen = false"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 cursor-pointer">
                                        <span>❤️</span> Istaklar ro'yxati
                                    </a>
                                    <a href="/student/xp"
                                        @click.prevent="navigateTo('/student/xp'); profileDropdownOpen = false"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 cursor-pointer">
                                        <span>🏆</span> Yutuqlarim
                                    </a>
                                    <a href="/student/settings"
                                        @click.prevent="navigateTo('/student/settings'); profileDropdownOpen = false"
                                        class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-700 cursor-pointer">
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
        <main class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 lg:pb-8">
            <slot />
        </main>

        <!-- Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 lg:hidden z-40 pb-safe">
            <div class="flex justify-around items-center h-16">
                <a href="/student/dashboard" @click.prevent="navigateTo('/student/dashboard')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/dashboard') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-[10px] font-medium">Bosh</span>
                </a>
                <a href="/student/courses" @click.prevent="navigateTo('/student/courses')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/courses') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="text-[10px] font-medium">Kurslar</span>
                </a>
                <a href="/student/english" @click.prevent="navigateTo('/student/english')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/english') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-[10px] font-medium">Ingliz tili</span>
                </a>
                <a href="/student/lab" @click.prevent="navigateTo('/student/lab')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/lab') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-[10px] font-medium">Lab</span>
                </a>
                <a href="/student/xp" @click.prevent="navigateTo('/student/xp')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/xp') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-[10px] font-medium">XP</span>
                </a>
                <a href="/student/profile" @click.prevent="navigateTo('/student/profile')" :class="[
                    'flex flex-col items-center justify-center w-full h-full space-y-1 cursor-pointer',
                    isActive('/student/profile') ? 'text-purple-600' : 'text-gray-500 hover:text-gray-900'
                ]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[10px] font-medium">Profil</span>
                </a>
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
