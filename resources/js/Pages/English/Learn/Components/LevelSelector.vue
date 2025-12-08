<template>
    <div class="w-full overflow-x-auto pb-4 hide-scrollbar">
        <div class="flex gap-4 min-w-max px-1">
            <LevelCard 
                v-for="level in levels" 
                :key="level.id" 
                :level="level"
                :is-selected="selected && selected.id === level.id"
                @select="$emit('select', level)"
            />
        </div>
    </div>
</template>

<script setup>
import LevelCard from './LevelCard.vue'

defineProps({
    levels: {
        type: Array,
        required: true
    },
    selected: {
        type: Object,
        default: null
    }
})

defineEmits(['select'])
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
