<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    directions: Array,
    tags: Array,
});

const form = useForm({
    title: '',
    direction_id: '',
    level: 'beginner',
    short_description: '',
    description: '',
    price: '',
    discount_price: '',
    is_free: false,
    requirements: [],
    what_will_learn: [],
    who_is_for: [],
    tags: [],
    // Helpers
    new_requirement: '',
    new_learn_item: '',
    new_target_audience: '',
});

const addItem = (field, newItemField) => {
    if (!form[newItemField]) return;
    form[field].push(form[newItemField]);
    form[newItemField] = '';
};

const removeItem = (field, index) => {
    form[field].splice(index, 1);
};

const submit = () => {
    form.post(route('teacher.courses.store'));
};
</script>

<template>

    <Head title="Yangi kurs yaratish" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/teacher/courses" class="text-gray-500 hover:text-gray-700">
                ← Orqaga
                </Link>
                <h2 class="text-xl font-semibold text-gray-800">Yangi kurs yaratish</h2>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Info -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Asosiy ma'lumotlar</h3>

                    <Input v-model="form.title" label="Kurs nomi" placeholder="Masalan: JavaScript Pro" required />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Yo'nalish</label>
                            <select v-model="form.direction_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                required>
                                <option value="" disabled>Tanlang</option>
                                <option v-for="dir in directions" :key="dir.id" :value="dir.id">{{ dir.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Daraja</label>
                            <select v-model="form.level"
                                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="beginner">Boshlang'ich</option>
                                <option value="intermediate">O'rta</option>
                                <option value="advanced">Yuqori</option>
                            </select>
                        </div>
                    </div>

                    <Input v-model="form.short_description" label="Qisqa tavsif" placeholder="Kurs haqida 1-2 gap bilan"
                        required />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To'liq tavsif</label>
                        <textarea v-model="form.description" rows="6"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Kurs haqida batafsil..." required></textarea>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Narxlash</h3>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_free"
                            class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                        <span class="text-gray-700 font-medium">Bepul kurs</span>
                    </label>

                    <div v-if="!form.is_free" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input v-model="form.price" label="Narxi (UZS)" type="number" required />
                        <Input v-model="form.discount_price" label="Chegirmali narx (ixtiyoriy)" type="number" />
                    </div>
                </div>

                <!-- Lists -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Qo'shimcha ma'lumotlar</h3>

                    <!-- Requirements -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Talablar</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="form.new_requirement"
                                @keydown.enter.prevent="addItem('requirements', 'new_requirement')" type="text"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                placeholder="Talab qo'shish...">
                            <button type="button" @click="addItem('requirements', 'new_requirement')"
                                class="px-4 py-2 bg-gray-100 rounded-xl hover:bg-gray-200">Qo'shish</button>
                        </div>
                        <ul class="space-y-1">
                            <li v-for="(item, index) in form.requirements" :key="index"
                                class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="text-emerald-500">•</span>
                                {{ item }}
                                <button type="button" @click="removeItem('requirements', index)"
                                    class="text-red-400 hover:text-red-600">×</button>
                            </li>
                        </ul>
                    </div>

                    <!-- What will learn -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nimalarni o'rganasiz</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="form.new_learn_item"
                                @keydown.enter.prevent="addItem('what_will_learn', 'new_learn_item')" type="text"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                placeholder="O'rganiladigan narsa...">
                            <button type="button" @click="addItem('what_will_learn', 'new_learn_item')"
                                class="px-4 py-2 bg-gray-100 rounded-xl hover:bg-gray-200">Qo'shish</button>
                        </div>
                        <ul class="space-y-1">
                            <li v-for="(item, index) in form.what_will_learn" :key="index"
                                class="flex items-center gap-2 text-sm text-gray-600">
                                <span class="text-emerald-500">✓</span>
                                {{ item }}
                                <button type="button" @click="removeItem('what_will_learn', index)"
                                    class="text-red-400 hover:text-red-600">×</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/teacher/courses"
                        class="px-6 py-3 bg-white text-gray-700 font-medium rounded-xl border border-gray-200 hover:bg-gray-50">
                    Bekor qilish
                    </Link>
                    <Button type="submit" :loading="form.processing">
                        Yaratish
                    </Button>
                </div>
            </form>
        </div>
    </TeacherLayout>
</template>
