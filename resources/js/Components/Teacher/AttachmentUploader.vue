<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    lesson: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    file: null,
    title: '',
    is_downloadable: true,
});

const fileInput = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        if (!form.title) {
            form.title = file.name.split('.').slice(0, -1).join('.');
        }
    }
};

const submit = () => {
    form.post(route('teacher.lessons.attachments.store', props.lesson.id), {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="true" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Fayl yuklash</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- File Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fayl</label>
                    <input ref="fileInput" type="file" @change="handleFileChange" class="block w-full text-sm text-gray-500
              file:mr-4 file:py-2 file:px-4
              file:rounded-full file:border-0
              file:text-sm file:font-semibold
              file:bg-indigo-50 file:text-indigo-700
              hover:file:bg-indigo-100" />
                    <div v-if="form.errors.file" class="text-red-500 text-xs mt-1">{{ form.errors.file }}</div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomi</label>
                    <input v-model="form.title" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Fayl nomi" />
                    <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                </div>

                <!-- Is Downloadable -->
                <div class="flex items-center">
                    <input v-model="form.is_downloadable" type="checkbox" id="is_downloadable"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                    <label for="is_downloadable" class="ml-2 block text-sm text-gray-900">
                        Yuklab olishga ruxsat berish
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Bekor qilish
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        Yuklash
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
