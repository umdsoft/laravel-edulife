<template>
    <div>
        <AdminHeader />
        <AdminSidebar />

        <main class="ml-64 pt-16 min-h-screen bg-gray-50">
            <div class="p-6">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import AdminHeader from '@/Components/Admin/Header.vue';
import AdminSidebar from '@/Components/Admin/Sidebar.vue';
</script>
