<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const teacherProfile = computed(() => page.props.auth.teacher_profile);

const sidebarOpen = ref(false);
const profileDropdownOpen = ref(false);

const navigation = [
    { name: 'Dashboard', href: '/teacher/dashboard', icon: 'home' },
    { name: 'Kurslarim', href: '/teacher/courses', icon: 'book-open' },
    { name: 'O\'quvchilarim', href: '/teacher/students', icon: 'users' },
    { name: 'Daromadlar', href: '/teacher/earnings', icon: 'currency-dollar' },
    { name: 'Mening Reytingim', href: '/teacher/rating', icon: 'star' },
    { name: 'Sharhlar', href: '/teacher/reviews', icon: 'chat-bubble-left' },
    { name: 'Statistika', href: '/teacher/analytics', icon: 'chart-bar' },
];

const bottomNavigation = [
    { name: 'Profil', href: '/teacher/profile', icon: 'user-circle' },
    { name: 'Bank hisoblar', href: '/teacher/bank-accounts', icon: 'building-library' },
];

const isActive = (href) => {
    return page.url.startsWith(href);
};

const logout = () => {
    router.post('/logout');
};

// Teacher level badge
const levelBadge = computed(() => {
    const level = teacherProfile.value?.level || 'new';
    const badges = {
        new: { label: 'Yangi', class: 'bg-gray-100 text-gray-600' },
        verified: { label: 'Tasdiqlangan', class: 'bg-blue-100 text-blue-600' },
        featured: { label: 'Featured', class: 'bg-purple-100 text-purple-600' },
        top: { label: 'Top', class: 'bg-yellow-100 text-yellow-600' },
    };
    return badges[level] || badges.new;
});

// Helper to get icon component (you might need to import these or use a library)
// For now, using simple SVG strings or components if available. 
// Assuming you have Heroicons or similar.
// Since I don't have the icon components, I'll use inline SVGs in the template based on the name.
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile sidebar backdrop -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform lg:translate-x-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <!-- Logo -->
            <div class="flex items-center gap-3 h-16 px-6 border-b border-gray-100">
                <span class="text-2xl">🎓</span>
                <div>
                    <span class="text-lg font-bold text-gray-900">EDULIFE</span>
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-600">
                        Teacher
                    </span>
                </div>
            </div>

            <!-- Teacher Info -->
            <div class="p-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <img v-if="user?.avatar_url" :src="user.avatar_url" :alt="user.full_name"
                            class="w-10 h-10 rounded-full object-cover" />
                        <span v-else class="text-emerald-600 font-medium">
                            {{ user?.first_name?.charAt(0) }}{{ user?.last_name?.charAt(0) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ user?.full_name }}
                        </p>
                        <span :class="['inline-flex px-2 py-0.5 text-xs font-medium rounded-full', levelBadge.class]">
                            {{ levelBadge.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <Link v-for="item in navigation" :key="item.name" :href="item.href" :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                    isActive(item.href)
                        ? 'bg-emerald-50 text-emerald-600'
                        : 'text-gray-600 hover:bg-gray-50'
                ]">
                <!-- Icons -->
                <svg v-if="item.icon === 'home'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <svg v-else-if="item.icon === 'book-open'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <svg v-else-if="item.icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg v-else-if="item.icon === 'currency-dollar'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="item.icon === 'chat-bubble-left'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <svg v-else-if="item.icon === 'chart-bar'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <svg v-else-if="item.icon === 'star'" class="w-5 h-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>

                {{ item.name }}
                </Link>

                <div class="pt-4 mt-4 border-t border-gray-100">
                    <Link v-for="item in bottomNavigation" :key="item.name" :href="item.href" :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'text-gray-600 hover:bg-gray-50'
                    ]">
                    <svg v-if="item.icon === 'user-circle'" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else-if="item.icon === 'building-library'" class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                    </svg>
                    {{ item.name }}
                    </Link>
                </div>

                <button @click="logout"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 w-full mt-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Chiqish
                </button>
            </nav>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top header -->
            <header
                class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 lg:px-8">
                <!-- Mobile menu button -->
                <button @click="sidebarOpen = true" class="p-2 -ml-2 text-gray-500 lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title slot -->
                <div class="flex-1">
                    <slot name="header" />
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-4">
                    <!-- Balance -->
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-emerald-50 rounded-lg">
                        <span class="text-emerald-600">💰</span>
                        <span class="text-sm font-medium text-emerald-600">
                            {{ teacherProfile?.balance ? new Intl.NumberFormat('uz-UZ').format(teacherProfile.balance) :
                                '0' }} UZS
                        </span>
                    </div>

                    <!-- Notifications -->
                    <Link :href="route('teacher.notifications.index')"
                        class="relative p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="$page.props.auth.unread_notifications_count > 0"
                        class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                        {{ $page.props.auth.unread_notifications_count }}
                    </span>
                    </Link>

                    <!-- New Course Button -->
                    <Link href="/teacher/courses/create"
                        class="hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi kurs
                    </Link>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-4 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
