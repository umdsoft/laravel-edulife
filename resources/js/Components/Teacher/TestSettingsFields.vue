<script setup>
/**
 * TestSettingsFields — title / description / attempts / shuffle options, shared by Tests/Create and Tests/Edit.
 * Binds to the page's useForm() object (field names unchanged).
 */
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Switch from '@/Components/UI/Switch.vue';

defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <Card title="Test ma'lumotlari">
        <div class="space-y-5">
            <Input v-model="form.title" label="Nomi" required maxlength="255" placeholder="Masalan: Kinematika bo'yicha nazorat"
                :error="form.errors.title" />
            <Textarea v-model="form.description" label="Tavsif" optional :rows="3" autoresize
                placeholder="O'quvchi test oldidan ko'radigan qisqa izoh" :error="form.errors.description" />
            <Input v-model.number="form.max_attempts" label="Urinishlar soni" type="number" min="1" inputmode="numeric" required
                hint="Bitta o'quvchi testni necha marta topshira oladi" class="sm:max-w-[12rem]" :error="form.errors.max_attempts" />
        </div>
    </Card>

    <Card title="Sozlamalar">
        <div class="space-y-4">
            <Switch v-model="form.shuffle_questions" label="Savollarni aralashtirish" description="Har bir o'quvchida savollar tartibi har xil bo'ladi." />
            <Switch v-model="form.shuffle_options" label="Javob variantlarini aralashtirish" description="Variantlar tartibi har safar o'zgaradi." />
            <Switch v-model="form.show_correct_answers" label="To'g'ri javoblarni ko'rsatish" description="Test tugagach o'quvchi to'g'ri javoblarni ko'radi." />
        </div>
    </Card>
</template>
