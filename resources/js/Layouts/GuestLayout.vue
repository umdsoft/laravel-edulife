<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    🎓 EDULIFE
                </h1>
                <p class="text-gray-500">O'zbekistonning eng yaxshi ta'lim platformasi</p>
            </div>

            <!-- Content Slot -->
            <div class="bg-white rounded-2xl shadow-sm p-8">
                <slot />
            </div>

            <!-- Footer -->
            <p class="text-center text-sm text-gray-400 mt-6">
                © 2025 EDULIFE LMS. Barcha huquqlar himoyalangan.
            </p>
        </div>
    </div>
</template>

<script setup>
// Minimal layout for auth pages
</script>
