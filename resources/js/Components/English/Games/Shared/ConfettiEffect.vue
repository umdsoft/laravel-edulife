<script setup>
import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
    active: Boolean,
})

const particles = ref([])
const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD']

let animationId = null

const createParticles = () => {
    particles.value = Array.from({ length: 50 }, () => ({
        id: Math.random(),
        x: Math.random() * 100,
        y: -10,
        color: colors[Math.floor(Math.random() * colors.length)],
        size: Math.random() * 8 + 4,
        speedY: Math.random() * 3 + 2,
        speedX: (Math.random() - 0.5) * 2,
    }))
    
    const animate = () => {
        particles.value = particles.value
            .map(p => ({ ...p, y: p.y + p.speedY, x: p.x + p.speedX }))
            .filter(p => p.y < 110)
        
        if (particles.value.length > 0) {
            animationId = requestAnimationFrame(animate)
        }
    }
    
    animationId = requestAnimationFrame(animate)
}

watch(() => props.active, (active) => {
    if (active) createParticles()
})

onUnmounted(() => {
    if (animationId) cancelAnimationFrame(animationId)
})
</script>

<template>
    <div v-if="active" class="fixed inset-0 pointer-events-none z-50 overflow-hidden">
        <div v-for="p in particles" :key="p.id"
            class="absolute rounded-full"
            :style="{
                left: `${p.x}%`,
                top: `${p.y}%`,
                width: `${p.size}px`,
                height: `${p.size}px`,
                backgroundColor: p.color,
            }"/>
    </div>
</template>
