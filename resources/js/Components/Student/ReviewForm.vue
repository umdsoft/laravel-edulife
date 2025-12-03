<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    course: Object,
    existingReview: Object,
});

const form = useForm({
    rating: props.existingReview?.rating || 5,
    comment: props.existingReview?.comment || '',
});

const hoverRating = ref(0);

const submit = () => {
    if (props.existingReview) {
        form.put(route('student.reviews.update', props.existingReview.id));
    } else {
        form.post(route('student.reviews.store', props.course.id));
    }
};
</script>

<template>
    <form @submit.prevent="submit" class="bg-white p-6 rounded-2xl border border-gray-100">
        <h3 class="text-lg font-bold text-gray-900 mb-4">
            {{ existingReview ? 'Sharhni yangilash' : 'Sharh qoldirish' }}
        </h3>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Baholash</label>
            <div class="flex items-center gap-1">
                <button v-for="star in 5" :key="star" type="button" @mouseenter="hoverRating = star"
                    @mouseleave="hoverRating = 0" @click="form.rating = star"
                    class="focus:outline-none transition-transform hover:scale-110">
                    <svg class="w-8 h-8"
                        :class="(hoverRating || form.rating) >= star ? 'text-yellow-400' : 'text-gray-300'"
                        fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                {{ form.rating }} / 5
            </p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Fikringiz</label>
            <textarea v-model="form.comment" rows="4"
                class="w-full border-gray-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                placeholder="Kurs haqida fikringizni yozing..."></textarea>
            <p class="text-xs text-gray-500 mt-1 text-right">
                {{ form.comment.length }} / 2000
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="form.processing"
                class="px-6 py-3 bg-purple-600 text-white font-medium rounded-xl hover:bg-purple-700 disabled:opacity-50 transition-colors">
                {{ existingReview ? 'Yangilash' : 'Yuborish' }}
            </button>
        </div>
    </form>
</template>
