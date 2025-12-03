<script setup>
defineProps({
    tabs: {
        type: Array,
        required: true
    },
    activeTab: {
        type: String,
        required: true
    }
});

defineEmits(['update:activeTab']);
</script>

<template>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button v-for="tab in tabs" :key="tab.id" @click="$emit('update:activeTab', tab.id)" :class="[
                activeTab === tab.id
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
            ]" :aria-current="activeTab === tab.id ? 'page' : undefined">
                {{ tab.name }}
                <span v-if="tab.count" :class="[
                    activeTab === tab.id ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-900',
                    'hidden ml-3 py-0.5 px-2.5 rounded-full text-xs font-medium md:inline-block'
                ]">
                    {{ tab.count }}
                </span>
            </button>
        </nav>
    </div>
</template>
