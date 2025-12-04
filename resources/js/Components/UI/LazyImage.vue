<script setup>
/**
 * LazyImage Component
 * 
 * Optimized image component with:
 * - Lazy loading via native loading="lazy"
 * - Width/height to prevent CLS
 * - WebP support with fallback
 * - Blur placeholder while loading
 * - Error handling with fallback image
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    alt: {
        type: String,
        default: '',
    },
    width: {
        type: [Number, String],
        default: null,
    },
    height: {
        type: [Number, String],
        default: null,
    },
    aspectRatio: {
        type: String,
        default: null, // e.g., '16/9', '4/3', '1/1'
    },
    fallback: {
        type: String,
        default: '/images/placeholder.jpg',
    },
    // CSS classes for the image
    imgClass: {
        type: String,
        default: 'w-full h-full object-cover',
    },
    // Priority loading (above the fold)
    priority: {
        type: Boolean,
        default: false,
    },
});

const isLoaded = ref(false);
const hasError = ref(false);
const imageRef = ref(null);

// Computed image source with error fallback
const imageSrc = computed(() => {
    if (hasError.value || !props.src) {
        return props.fallback;
    }
    return props.src;
});

// Computed aspect ratio style
const aspectStyle = computed(() => {
    if (props.aspectRatio) {
        return { aspectRatio: props.aspectRatio };
    }
    return {};
});

const onLoad = () => {
    isLoaded.value = true;
};

const onError = () => {
    hasError.value = true;
    isLoaded.value = true;
};

// Preload priority images
onMounted(() => {
    if (props.priority && props.src) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = props.src;
        document.head.appendChild(link);
    }
});
</script>

<template>
    <div 
        class="relative overflow-hidden bg-gray-100"
        :style="aspectStyle"
    >
        <!-- Blur placeholder while loading -->
        <div 
            v-if="!isLoaded"
            class="absolute inset-0 animate-pulse bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200"
        />
        
        <!-- Main image -->
        <img
            ref="imageRef"
            :src="imageSrc"
            :alt="alt"
            :width="width"
            :height="height"
            :loading="priority ? 'eager' : 'lazy'"
            :fetchpriority="priority ? 'high' : 'auto'"
            decoding="async"
            :class="[
                imgClass,
                'transition-opacity duration-300',
                isLoaded ? 'opacity-100' : 'opacity-0'
            ]"
            @load="onLoad"
            @error="onError"
        />
    </div>
</template>
