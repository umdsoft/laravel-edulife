<template>
    <header class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-100 z-50">
        <div class="h-full px-6 flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-gray-900">🎓 EDULIFE</h1>
                <span class="px-2 py-1 text-xs font-medium text-white bg-red-500 rounded-md">
                    Admin
                </span>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-4">
                <!-- Search (placeholder) -->
                <div class="hidden md:block w-64">
                    <input type="text" placeholder="Qidirish..."
                        class="w-full bg-gray-50 border-0 rounded-lg px-4 py-2 text-sm placeholder-gray-400 focus:ring-2 focus:ring-primary/20" />
                </div>

                <!-- Notifications -->
                <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>

                <!-- Profile -->
                <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                    <div
                        class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-medium text-sm">
                        {{ initials }}
                    </div>
                    <div class="hidden md:block">
                        <p class="text-sm font-medium text-gray-900">{{ user.full_name }}</p>
                        <p class="text-xs text-gray-500 capitalize">{{ user.role }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const initials = computed(() => {
    if (!user.value) return '';
    return (user.value.first_name?.[0] || '') + (user.value.last_name?.[0] || '');
});
</script>
