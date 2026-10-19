<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';
import { PencilSquareIcon, PhotoIcon, ChevronRightIcon, AcademicCapIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    template: Object,
    recentCertificates: Array,
});

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (v) => {
    if (!v) return '—';
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? v : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};

const certificates = computed(() => props.recentCertificates || []);
const studentName = (c) => c.user?.full_name || [c.user?.first_name, c.user?.last_name].filter(Boolean).join(' ') || 'O\'quvchi';

const breadcrumbs = computed(() => [
    { label: 'Sertifikatlar', href: route('admin.certificates.index') },
    { label: 'Shablonlar', href: route('admin.certificate-templates.index') },
    { label: props.template.name },
]);
</script>

<template>
    <AdminLayout>
        <Head :title="template.name" />

        <PageHeader :title="template.name" :description="template.description || null" :breadcrumbs="breadcrumbs">
            <template #meta>
                <Badge :variant="template.is_active ? 'success' : 'neutral'" dot>{{ template.is_active ? 'Faol' : 'Nofaol' }}</Badge>
                <Badge v-if="template.is_default" variant="warning" :icon="StarIcon">Asosiy shablon</Badge>
            </template>
            <template #actions>
                <Button :icon-left="PencilSquareIcon" :href="route('admin.certificate-templates.edit', template.id)">Tahrirlash</Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Ko'rinishi">
                    <div class="relative aspect-[1.414] w-full overflow-hidden rounded-md border border-border bg-surface-2">
                        <img v-if="template.thumbnail_path" :src="`/storage/${template.thumbnail_path}`"
                            :alt="`${template.name} shabloni ko'rinishi`" class="h-full w-full object-contain" />
                        <EmptyState v-else :icon="PhotoIcon" title="Ko'rinish mavjud emas"
                            description="Tahrirlash sahifasida thumbnail rasm yuklang" size="sm"
                            class="absolute inset-0 flex flex-col items-center justify-center" />
                    </div>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Ma'lumot">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Yo'nalish</dt>
                            <dd class="text-right font-medium">{{ template.direction?.name || "Barcha yo'nalishlar" }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Kurs</dt>
                            <dd class="text-right font-medium">{{ template.course?.title || 'Barcha kurslar' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Berilgan sertifikatlar</dt>
                            <dd class="text-right font-medium tabular-nums">{{ template.usage_count || 0 }} ta</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Yaratilgan</dt>
                            <dd class="text-right font-medium tabular-nums">{{ formatDate(template.created_at) }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card padding="none" title="So'nggi sertifikatlar">
                    <ul v-if="certificates.length" class="divide-y divide-border">
                        <li v-for="cert in certificates" :key="cert.id">
                            <Link :href="route('admin.certificates.show', cert.id)"
                                class="focus-ring flex items-center gap-3 px-5 py-3 transition-colors hover:bg-surface-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-fg">{{ studentName(cert) }}</p>
                                    <p class="truncate font-mono text-xs text-fg-subtle">{{ cert.certificate_code }}</p>
                                </div>
                                <ChevronRightIcon class="h-4 w-4 shrink-0 text-fg-subtle" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="AcademicCapIcon" title="Hozircha sertifikat yo'q" size="sm" class="py-8" />
                </Card>
            </aside>
        </div>
    </AdminLayout>
</template>
