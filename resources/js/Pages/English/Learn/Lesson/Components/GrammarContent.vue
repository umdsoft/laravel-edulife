<script setup>
import { computed } from 'vue'

const props = defineProps({
    content: {
        type: [String, Object, Array],
        required: true
    },
    type: {
        type: String,
        default: 'text' // text, tip, warning, table, rule
    }
})

/**
 * Matnni xavfsiz HTML ga aylantirish
 * Faqat ruxsat etilgan teglarni qoldiradi
 */
const sanitizeHtml = (text) => {
    if (!text || typeof text !== 'string') return text
    
    // Ruxsat etilgan teglar
    const allowedTags = ['strong', 'b', 'i', 'em', 'u', 'span', 'br']
    
    // Teglarni to'g'ri formatlash
    let result = text
    
    // Mavjud HTML teglarni qayta ishlash
    allowedTags.forEach(tag => {
        // Opening tags
        const openRegex = new RegExp(`<${tag}[^>]*>`, 'gi')
        result = result.replace(openRegex, `<${tag}>`)
        
        // Closing tags
        const closeRegex = new RegExp(`</${tag}>`, 'gi')
        result = result.replace(closeRegex, `</${tag}>`)
    })
    
    return result
}

/**
 * Markdown-style formatting
 * **bold** -> <strong>bold</strong>
 * *italic* -> <em>italic</em>
 * __underline__ -> <u>underline</u>
 */
const formatText = (text) => {
    if (!text || typeof text !== 'string') return text
    
    let result = text
    
    // **bold** -> <strong>
    result = result.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold text-indigo-600 dark:text-indigo-400">$1</strong>')
    
    // *italic* -> <em>
    result = result.replace(/\*([^*]+)\*/g, '<em class="italic">$1</em>')
    
    // __underline__ -> <u>
    result = result.replace(/__([^_]+)__/g, '<u class="underline decoration-2 decoration-amber-500">$1</u>')
    
    // `code` -> <code>
    result = result.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono text-pink-600 dark:text-pink-400">$1</code>')
    
    // Existing HTML tags sanitize qilish
    result = sanitizeHtml(result)
    
    return result
}

const formattedContent = computed(() => {
    if (typeof props.content === 'string') {
        return formatText(props.content)
    }
    return props.content
})
</script>

<template>
    <!-- Text type -->
    <p 
        v-if="type === 'text'" 
        v-html="formattedContent"
        class="text-gray-700 dark:text-gray-300 leading-relaxed"
    ></p>
    
    <!-- Tip type -->
    <div 
        v-else-if="type === 'tip'"
        class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded-r-xl"
    >
        <span class="text-2xl shrink-0">💡</span>
        <div>
            <p class="font-semibold text-amber-700 dark:text-amber-300 mb-1">Eslab qoling!</p>
            <p 
                v-html="formattedContent"
                class="text-amber-600 dark:text-amber-400 text-sm"
            ></p>
        </div>
    </div>
    
    <!-- Warning type -->
    <div 
        v-else-if="type === 'warning'"
        class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 rounded-r-xl"
    >
        <span class="text-2xl shrink-0">⚠️</span>
        <div>
            <p class="font-semibold text-red-700 dark:text-red-300 mb-1">Diqqat!</p>
            <p 
                v-html="formattedContent"
                class="text-red-600 dark:text-red-400 text-sm"
            ></p>
        </div>
    </div>
    
    <!-- Rule type -->
    <div 
        v-else-if="type === 'rule'"
        class="flex items-start gap-3 p-4 bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-400 rounded-r-xl"
    >
        <span class="text-2xl shrink-0">📐</span>
        <div>
            <p class="font-semibold text-indigo-700 dark:text-indigo-300 mb-1">Qoida</p>
            <p 
                v-html="formattedContent"
                class="text-indigo-600 dark:text-indigo-400"
            ></p>
        </div>
    </div>
    
    <!-- Default -->
    <span v-else v-html="formattedContent"></span>
</template>
