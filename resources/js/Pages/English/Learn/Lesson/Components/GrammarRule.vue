<script setup>
import { computed } from 'vue'
import GrammarContent from './GrammarContent.vue'
import GrammarTable from './GrammarTable.vue'

const props = defineProps({
    rule: {
        type: Object,
        required: true
    }
})

// Formula formatlash
const formatFormula = (formula) => {
    if (!formula) return ''
    
    // + ni ajratuvchi sifatida formatlash
    return formula
        .split('+')
        .map(part => `<span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 rounded font-semibold text-indigo-700 dark:text-indigo-300 text-sm md:text-base">${part.trim()}</span>`)
        .join('<span class="mx-1 text-gray-400 font-bold">+</span>')
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="p-5 bg-gradient-to-r from-indigo-500 to-purple-600">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">
                    {{ rule.title }}
                </h3>
                <span 
                    v-if="rule.importance"
                    class="text-lg opacity-90"
                    title="Importance"
                >
                    {{ rule.importance }}
                </span>
            </div>
            <p 
                v-if="rule.titleUz"
                class="text-indigo-100 text-sm mt-1 font-medium"
            >
                {{ rule.titleUz }}
            </p>
        </div>
        
        <!-- Content -->
        <div class="p-5 space-y-6">
            
            <!-- Introduction -->
            <div v-if="rule.explanation?.intro" class="prose dark:prose-invert max-w-none">
                <GrammarContent :content="rule.explanation.intro" type="text" />
            </div>
            
            <!-- Formula -->
            <div 
                v-if="rule.explanation?.formula?.pattern"
                class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-600"
            >
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg">📐</span>
                    <span class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Formula</span>
                </div>
                
                <div 
                    class="flex flex-wrap items-center gap-y-2"
                    v-html="formatFormula(rule.explanation.formula.pattern)"
                ></div>
                
                <p 
                    v-if="rule.explanation.formula.patternUz"
                    class="text-sm text-gray-500 dark:text-gray-400 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 italic"
                >
                    {{ rule.explanation.formula.patternUz }}
                </p>
            </div>
            
            <!-- Rules Table -->
            <GrammarTable
                v-if="rule.explanation?.rules"
                :title="rule.explanation.visualTable?.title"
                :headers="['Olmosh', 'Fe\'l', 'Misol', 'Tarjima']"
                :rows="rule.explanation.rules.map(r => [
                    r.subject,
                    `**${r.verb}**`,
                    r.example,
                    r.translation
                ])"
                :highlightColumn="1"
            />
            
            <!-- Examples -->
            <div v-if="rule.explanation?.formula?.examples" class="space-y-3">
                <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-emerald-500">📝</span> Misollar
                </h4>
                <div class="grid gap-3">
                    <div 
                        v-for="(example, index) in rule.explanation.formula.examples"
                        :key="index"
                        class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700 rounded-xl transition-transform hover:scale-[1.01]"
                    >
                        <span class="text-2xl pt-1">{{ example.flag || '🔹' }}</span>
                        <div class="flex-1">
                            <p class="font-bold text-gray-900 dark:text-white text-lg mb-1">
                                {{ example.english }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ example.uzbek }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Memory Trick -->
            <GrammarContent 
                v-if="rule.explanation?.visualTable?.memoryTrick"
                :content="rule.explanation.visualTable.memoryTrick"
                type="tip"
            />
            
            <!-- Countries/Cities Lists -->
            <div v-if="rule.explanation?.countries || rule.explanation?.cities" class="space-y-4">
                <div v-if="rule.explanation?.countries">
                     <h4 class="font-bold text-gray-900 dark:text-white mb-3">Davlatlar (Countries)</h4>
                     <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        <div 
                            v-for="country in rule.explanation.countries"
                            :key="country.english"
                            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
                        >
                            <span class="text-2xl">{{ country.flag }}</span>
                            <div class="leading-tight">
                                <p class="font-bold text-gray-900 dark:text-white text-sm">{{ country.english }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ country.uzbek }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                 <div v-if="rule.explanation?.cities">
                     <h4 class="font-bold text-gray-900 dark:text-white mb-3">Shaharlar (Cities)</h4>
                     <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        <div 
                            v-for="city in rule.explanation.cities"
                            :key="city.english"
                            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
                        >
                            <span class="text-2xl">🏙️</span>
                            <div class="leading-tight">
                                <p class="font-bold text-gray-900 dark:text-white text-sm">{{ city.english }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ city.uzbek }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Warning -->
             <div v-if="rule.explanation?.warning">
                <GrammarContent :content="rule.explanation.warning" type="warning" />
             </div>
            
        </div>
    </div>
</template>
