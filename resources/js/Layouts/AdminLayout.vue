<template>
    <div>
        <AdminHeader />
        <AdminSidebar />

        <main class="ml-64 pt-16 min-h-screen bg-gray-50">
            <div class="p-6">
                <slot />
            </div>
        </main>

        <!-- Toast Notifications -->
        <Toast v-for="(toast, index) in toasts" :key="index" :type="toast.type" :message="toast.message"
            @close="removeToast(index)" />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminHeader from '@/Components/Admin/Header.vue';
import AdminSidebar from '@/Components/Admin/Sidebar.vue';
import Toast from '@/Components/UI/Toast.vue';

const page = usePage();
const toasts = ref([]);

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toasts.value.push({ type: 'success', message: flash.success });
        }
        if (flash?.error) {
            toasts.value.push({ type: 'error', message: flash.error });
        }
        if (flash?.warning) {
            toasts.value.push({ type: 'warning', message: flash.warning });
        }
        if (flash?.info) {
            toasts.value.push({ type: 'info', message: flash.info });
        }
    },
    { deep: true, immediate: true }
);

const removeToast = (index) => {
    toasts.value.splice(index, 1);
};
</script>
