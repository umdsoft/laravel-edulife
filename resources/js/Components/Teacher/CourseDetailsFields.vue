<script setup>
/**
 * CourseDetailsFields — the course form sections shared by Courses/Create and Courses/Edit.
 * Binds directly to the page's useForm() object (field names unchanged).
 *   <CourseDetailsFields :form="form" :directions="directions" :tags="tags" />
 */
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/20/solid';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Select from '@/Components/UI/Select.vue';
import Switch from '@/Components/UI/Switch.vue';
import FormField from '@/Components/UI/FormField.vue';
import ListEditor from './ListEditor.vue';

const props = defineProps({
    form: { type: Object, required: true },
    directions: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
});

const levels = [
    { value: 'beginner', label: 'Boshlang\'ich' },
    { value: 'intermediate', label: 'O\'rta' },
    { value: 'advanced', label: 'Yuqori' },
];

const descriptionLength = computed(() => String(props.form.description || '').length);

const hasTag = (id) => (props.form.tags || []).includes(id);
const toggleTag = (id) => {
    const current = props.form.tags || [];
    props.form.tags = hasTag(id) ? current.filter((t) => t !== id) : [...current, id];
};
</script>

<template>
    <div class="space-y-6">
        <Card title="Asosiy ma'lumotlar" description="O'quvchilar kurs kartochkasida birinchi bo'lib shu ma'lumotlarni ko'radi.">
            <div class="space-y-5">
                <Input v-model="form.title" label="Kurs nomi" placeholder="Masalan: Algebra asoslari" required maxlength="255"
                    :error="form.errors.title" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <Select v-model="form.direction_id" label="Yo'nalish" placeholder="Tanlang" :options="directions"
                        value-key="id" label-key="name" required :error="form.errors.direction_id" />
                    <Select v-model="form.level" label="Daraja" :options="levels" :error="form.errors.level" />
                </div>

                <Textarea v-model="form.short_description" label="Qisqa tavsif" placeholder="Kurs haqida 1–2 gap" :rows="2"
                    maxlength="200" show-count required :error="form.errors.short_description" />

                <Textarea v-model="form.description" label="To'liq tavsif" placeholder="Kurs nimani o'rgatadi, qanday tuzilgan, qanday natija beradi…"
                    :rows="8" autoresize required :error="form.errors.description"
                    :hint="descriptionLength < 100 ? `Kamida 100 belgi — yana ${100 - descriptionLength} ta` : `${descriptionLength} belgi`" />
            </div>
        </Card>

        <Card title="Narx">
            <div class="space-y-5">
                <Switch v-model="form.is_free" label="Bepul kurs" description="Barcha o'quvchilar uchun to'lovsiz ochiq bo'ladi." />

                <div v-if="!form.is_free" class="grid gap-5 sm:grid-cols-2">
                    <Input v-model="form.price" label="Narxi" type="number" min="0" step="1000" inputmode="numeric" required
                        :error="form.errors.price">
                        <template #trailing><span class="pr-2 text-13 text-fg-subtle">so'm</span></template>
                    </Input>
                    <Input v-model="form.discount_price" label="Chegirmali narx" type="number" min="0" step="1000" inputmode="numeric"
                        optional hint="Asl narxdan kam bo'lishi kerak" :error="form.errors.discount_price">
                        <template #trailing><span class="pr-2 text-13 text-fg-subtle">so'm</span></template>
                    </Input>
                </div>
            </div>
        </Card>

        <Card title="Kurs haqida" description="Kurs sahifasida ro'yxat ko'rinishida chiqadi. Enter bilan qo'shing.">
            <div class="space-y-6">
                <ListEditor v-model="form.what_will_learn" label="Nimalarni o'rganasiz" placeholder="Masalan: Kvadrat tenglamalarni yechish"
                    optional :error="form.errors.what_will_learn" />
                <ListEditor v-model="form.requirements" label="Talablar" placeholder="Masalan: 8-sinf algebra bilimi"
                    optional :error="form.errors.requirements" />
                <ListEditor v-model="form.who_is_for" label="Kimlar uchun" placeholder="Masalan: 9–11-sinf o'quvchilari"
                    optional :error="form.errors.who_is_for" />
            </div>
        </Card>

        <Card v-if="tags.length" title="Teglar" description="Qidiruvda kursni topishga yordam beradi.">
            <FormField as="fieldset" label="Teglarni tanlang" optional :error="form.errors.tags">
                <div class="flex flex-wrap gap-2">
                    <button v-for="tag in tags" :key="tag.id" type="button" :aria-pressed="hasTag(tag.id)" :class="[
                        'focus-ring inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-13 font-medium ring-1 ring-inset transition-colors duration-150',
                        hasTag(tag.id) ? 'bg-brand-50 text-brand ring-brand-200' : 'bg-surface text-fg-muted ring-border-strong hover:bg-surface-2 hover:text-fg',
                    ]" @click="toggleTag(tag.id)">
                        <CheckIcon v-if="hasTag(tag.id)" class="h-3.5 w-3.5" aria-hidden="true" />
                        {{ tag.name }}
                    </button>
                </div>
            </FormField>
        </Card>
    </div>
</template>
