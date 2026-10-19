<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { StarIcon } from '@heroicons/vue/20/solid';
import Textarea from '@/Components/UI/Textarea.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';

const props = defineProps({
    course: Object,
    existingReview: Object,
});

const form = useForm({
    rating: props.existingReview?.rating || 5,
    comment: props.existingReview?.comment || '',
});

const hoverRating = ref(0);

const labels = { 1: 'Yomon', 2: 'Qoniqarsiz', 3: "O'rtacha", 4: 'Yaxshi', 5: "A'lo" };
const shown = computed(() => hoverRating.value || form.rating);

const submit = () => {
    if (props.existingReview) {
        form.put(route('student.reviews.update', props.existingReview.id));
    } else {
        form.post(route('student.reviews.store', props.course.id));
    }
};

// Arrow keys change the rating like a native radio group
const onKey = (e) => {
    if (['ArrowRight', 'ArrowUp'].includes(e.key)) form.rating = Math.min(5, form.rating + 1);
    else if (['ArrowLeft', 'ArrowDown'].includes(e.key)) form.rating = Math.max(1, form.rating - 1);
    else return;
    e.preventDefault();
};
</script>

<template>
    <Card :title="existingReview ? 'Sharhni yangilash' : 'Sharh qoldirish'"
        description="Fikringiz boshqa o'quvchilarga va ustozga yordam beradi.">
        <form class="space-y-6" @submit.prevent="submit">
            <fieldset>
                <legend class="mb-2 text-sm font-medium text-fg">Baholash</legend>
                <div class="flex flex-wrap items-center gap-3">
                    <div role="radiogroup" aria-label="Baho" class="flex items-center gap-0.5" @keydown="onKey"
                        @mouseleave="hoverRating = 0">
                        <button v-for="star in 5" :key="star" type="button" role="radio" :aria-checked="form.rating === star"
                            :aria-label="`${star} yulduz — ${labels[star]}`" :tabindex="form.rating === star ? 0 : -1"
                            class="focus-ring rounded-sm p-0.5 transition-transform duration-150 hover:scale-110 motion-reduce:hover:scale-100"
                            @mouseenter="hoverRating = star" @click="form.rating = star">
                            <StarIcon :class="['h-8 w-8', shown >= star ? 'text-warning-600' : 'text-surface-3']" aria-hidden="true" />
                        </button>
                    </div>
                    <span class="text-sm text-fg-muted">
                        <span class="font-semibold text-fg tabular-nums">{{ shown }}</span> / 5 · {{ labels[shown] }}
                    </span>
                </div>
                <p v-if="form.errors.rating" class="mt-1.5 text-13 text-danger">{{ form.errors.rating }}</p>
            </fieldset>

            <Textarea v-model="form.comment" label="Fikringiz" :rows="5" :maxlength="2000" show-count autoresize
                placeholder="Kurs sizga nima berdi? Nimani yaxshilash mumkin?" :error="form.errors.comment" />

            <div class="flex justify-end">
                <Button type="submit" :loading="form.processing">
                    {{ existingReview ? 'Yangilash' : 'Yuborish' }}
                </Button>
            </div>
        </form>
    </Card>
</template>
