<script setup>
/**
 * OlympiadForm — fields shared by Admin/Olympiad/Create and Edit (field names unchanged).
 *   <OlympiadForm :form="form" :types="types" … mode="edit" submit-label="Saqlash" @submit="submit" />
 */
import { computed } from 'vue';
import { Alert, Button, Card, Input, Select, Switch, Textarea } from '@/Components/UI';
import { CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: { type: Object, required: true },
    types: { type: Array, default: () => [] },
    stages: { type: Array, default: () => [] },
    series: { type: Array, default: () => [] },
    regions: { type: Array, default: () => [] },
    mode: { type: String, default: 'create' }, // create | edit
    submitLabel: { type: String, default: 'Saqlash' },
    cancelHref: { type: String, required: true },
});
const emit = defineEmits(['submit']);

const typeOptions = computed(() => (props.types || []).map((t) => ({ value: t.id, label: t.display_name_uz || t.display_name })));
const stageOptions = computed(() => (props.stages || []).map((s) => ({ value: s.id, label: s.display_name })));
const seriesOptions = computed(() => (props.series || []).map((s) => ({ value: s.id, label: s.name })));
const regionOptions = computed(() => (props.regions || []).map((r) => ({ value: r.id, label: r.name })));

const sectionsNav = [
    { id: 'basic', label: 'Asosiy' },
    { id: 'schedule', label: 'Jadval va narx' },
    { id: 'config', label: 'Demo va anti-cheat' },
    { id: 'rewards', label: 'Mukofotlar' },
];

const errorCount = computed(() => Object.keys(props.form.errors || {}).length);
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[180px_minmax(0,1fr)]">
        <!-- Section index (desktop) -->
        <nav class="hidden lg:block" aria-label="Forma bo'limlari">
            <ul class="sticky top-24 space-y-1 text-13">
                <li v-for="s in sectionsNav" :key="s.id">
                    <a :href="`#${s.id}`"
                        class="focus-ring block rounded-md px-3 py-2 text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                        {{ s.label }}
                    </a>
                </li>
            </ul>
        </nav>

        <form class="max-w-3xl space-y-6" novalidate @submit.prevent="emit('submit')">
            <Alert v-if="errorCount" variant="danger" title="Formada xatolar bor">
                {{ errorCount }} ta maydonni tekshiring — xato maydonlar qizil bilan belgilangan.
            </Alert>

            <!-- Basic -->
            <Card id="basic" title="Asosiy ma'lumotlar" class="scroll-mt-24">
                <div class="space-y-5">
                    <Input v-model="form.title" label="Nomi" required placeholder="Masalan: Matematika olimpiadasi 2026"
                        :error="form.errors.title" />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <Select v-model="form.olympiad_type_id" label="Turi" required placeholder="Tanlang"
                            :options="typeOptions" :error="form.errors.olympiad_type_id" />
                        <Select v-model="form.stage_id" label="Bosqich" optional placeholder="Tanlang"
                            :options="stageOptions" :error="form.errors.stage_id" />
                        <Select v-model="form.series_id" label="Seriya" optional placeholder="Tanlang"
                            :options="seriesOptions" :error="form.errors.series_id" />
                        <Select v-model="form.region_id" label="Viloyat" optional placeholder="Barcha viloyatlar"
                            :options="regionOptions" :error="form.errors.region_id" />
                    </div>

                    <Textarea v-model="form.short_description" label="Qisqa tavsif" optional :rows="2" :maxlength="500"
                        show-count hint="Ro'yxat va kartochkalarda ko'rinadi" :error="form.errors.short_description" />
                    <Textarea v-model="form.description" label="To'liq tavsif" optional :rows="5" autoresize
                        :error="form.errors.description" />
                    <Textarea v-model="form.rules" label="Qoidalar" optional :rows="4" autoresize
                        :error="form.errors.rules" />
                </div>
            </Card>

            <!-- Schedule -->
            <Card id="schedule" title="Jadval va narx" description="Vaqtlar mahalliy vaqt bo'yicha" class="scroll-mt-24">
                <div class="space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <Input v-model="form.olympiad_start_at" type="datetime-local" label="Olimpiada boshlanishi" required
                            :error="form.errors.olympiad_start_at" />
                        <Input v-model="form.olympiad_end_at" type="datetime-local" label="Olimpiada tugashi" required
                            :error="form.errors.olympiad_end_at" />
                        <Input v-model="form.registration_start_at" type="datetime-local" label="Ro'yxat boshlanishi"
                            optional :error="form.errors.registration_start_at" />
                        <Input v-model="form.registration_end_at" type="datetime-local" label="Ro'yxat tugashi" optional
                            :error="form.errors.registration_end_at" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <Input v-model.number="form.registration_fee" type="number" min="0" step="1000" inputmode="numeric"
                            label="Ro'yxatdan o'tish narxi" hint="0 — bepul" :error="form.errors.registration_fee">
                            <template #trailing><span class="mr-2 text-13 text-fg-subtle">so'm</span></template>
                        </Input>
                        <Input v-model.number="form.max_participants" type="number" min="1" inputmode="numeric"
                            label="Maksimal ishtirokchilar" optional placeholder="Cheklanmagan"
                            :error="form.errors.max_participants" />
                    </div>
                </div>
            </Card>

            <!-- Config -->
            <Card id="config" title="Demo va anti-cheat" class="scroll-mt-24">
                <div class="space-y-6">
                    <section class="space-y-4" aria-labelledby="demo-heading">
                        <h4 id="demo-heading" class="text-sm font-semibold text-fg">Demo test</h4>
                        <Switch v-model="form.demo_config.enabled" label="Demo testni yoqish"
                            description="Ishtirokchilar olimpiadadan oldin mashq qilishi mumkin" />
                        <div v-if="form.demo_config.enabled" class="grid gap-5 sm:grid-cols-2">
                            <Input v-model.number="form.demo_config.price" type="number" min="0" inputmode="numeric"
                                label="Demo narxi" :error="form.errors['demo_config.price']">
                                <template #trailing><span class="mr-2 text-13 text-fg-subtle">so'm</span></template>
                            </Input>
                            <Input v-model.number="form.demo_config.max_attempts" type="number" min="1" inputmode="numeric"
                                label="Maksimal urinishlar" :error="form.errors['demo_config.max_attempts']" />
                        </div>
                    </section>

                    <section class="space-y-4 border-t border-border pt-6" aria-labelledby="ac-heading">
                        <h4 id="ac-heading" class="text-sm font-semibold text-fg">Anti-cheat</h4>
                        <Switch v-model="form.anti_cheat_config.fullscreen_required" label="To'liq ekran talab qilinadi"
                            description="Ishtirokchi to'liq ekrandan chiqsa ogohlantiriladi" />
                        <Switch v-model="form.anti_cheat_config.device_lock" label="Qurilmani qulflash"
                            description="Bitta urinish faqat bitta qurilmadan" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Input v-model.number="form.anti_cheat_config.max_tab_switches" type="number" min="1"
                                inputmode="numeric" label="Maks. tab almashtirish"
                                :error="form.errors['anti_cheat_config.max_tab_switches']" />
                            <Input v-model.number="form.anti_cheat_config.max_warnings" type="number" min="1"
                                inputmode="numeric" label="Maks. ogohlantirish"
                                :error="form.errors['anti_cheat_config.max_warnings']" />
                        </div>
                    </section>

                    <section v-if="mode === 'create'" class="border-t border-border pt-6">
                        <Switch v-model="form.create_default_sections" label="Standart bo'limlarni yaratish"
                            description="Tanlangan tur asosida bo'limlar avtomatik qo'shiladi" />
                    </section>
                </div>
            </Card>

            <!-- Rewards -->
            <Card id="rewards" title="Coin mukofotlari" class="scroll-mt-24">
                <div class="grid gap-5 sm:grid-cols-2">
                    <Input v-model.number="form.reward_config.coins_per_correct" type="number" min="0" inputmode="numeric"
                        label="Har bir to'g'ri javob uchun" :error="form.errors['reward_config.coins_per_correct']">
                        <template #trailing><span class="mr-2 text-13 text-fg-subtle">coin</span></template>
                    </Input>
                    <Input v-model.number="form.reward_config.top_10_coins" type="number" min="0" inputmode="numeric"
                        label="Top 10 uchun bonus" :error="form.errors['reward_config.top_10_coins']">
                        <template #trailing><span class="mr-2 text-13 text-fg-subtle">coin</span></template>
                    </Input>
                </div>
            </Card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <Button variant="secondary" :href="cancelHref">Bekor qilish</Button>
                <Button type="submit" :icon-left="CheckIcon" :loading="form.processing">{{ submitLabel }}</Button>
            </div>
        </form>
    </div>
</template>
