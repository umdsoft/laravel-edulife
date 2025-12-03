<script setup>
defineProps({
    tournament: Object,
});
</script>

<template>
    <div class="overflow-x-auto py-8">
        <div class="flex gap-16 min-w-max px-8">
            <!-- Round 1 -->
            <div class="flex flex-col justify-around gap-8">
                <div v-for="i in 4" :key="i"
                    class="w-64 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-500">Match {{ i }}</span>
                        <span class="text-xs text-gray-400">Pending</span>
                    </div>
                    <div class="p-2 space-y-2">
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">Player 1</span>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">Player 2</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Round 2 (Semi-Finals) -->
            <div class="flex flex-col justify-around gap-16">
                <div v-for="i in 2" :key="i"
                    class="w-64 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden relative">
                    <!-- Connector Lines (Simplified) -->
                    <div class="absolute -left-8 top-1/2 w-8 h-px bg-gray-300"></div>

                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-500">Semi-Final {{ i }}</span>
                    </div>
                    <div class="p-2 space-y-2">
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">TBD</span>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">TBD</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final -->
            <div class="flex flex-col justify-center">
                <div class="w-64 bg-white rounded-lg border-2 border-yellow-400 shadow-md overflow-hidden relative">
                    <!-- Connector Lines -->
                    <div class="absolute -left-8 top-1/2 w-8 h-px bg-gray-300"></div>

                    <div class="px-4 py-2 bg-yellow-50 border-b border-yellow-100 flex justify-between items-center">
                        <span class="text-xs font-bold text-yellow-700">Final</span>
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div class="p-2 space-y-2">
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">TBD</span>
                        </div>
                        <div class="flex items-center gap-2 p-2 rounded bg-gray-50">
                            <div class="w-6 h-6 bg-gray-200 rounded-full"></div>
                            <span class="text-sm text-gray-400">TBD</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
