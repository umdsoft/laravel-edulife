<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    isEnrolled: Boolean,
});

const loading = ref(false);

const enroll = () => {
    if (props.isEnrolled) {
        router.visit(route('student.courses.show', props.course.slug));
        return;
    }

    loading.value = true;
    router.post(route('student.courses.enroll', props.course.id), {}, {
        onFinish: () => loading.value = false,
    });
};
</script>

<template>
    <button @click="enroll" :disabled="loading" :class="[
        'w-full py-3 px-6 rounded-xl font-bold text-white transition-all transform hover:scale-[1.02] active:scale-[0.98]',
        isEnrolled
            ? 'bg-green-500 hover:bg-green-600 shadow-green-200'
            : 'bg-purple-600 hover:bg-purple-700 shadow-purple-200',
        loading ? 'opacity-75 cursor-not-allowed' : 'shadow-lg'
    ]">
        <span v-if="loading" class="flex items-center justify-center gap-2">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            Yuklanmoqda...
        </span>
        <span v-else>
            {{ isEnrolled ? 'Davom ettirish' : (course.is_free ? "Bepul yozilish" : "Sotib olish") }}
        </span>
    </button>
</template>
