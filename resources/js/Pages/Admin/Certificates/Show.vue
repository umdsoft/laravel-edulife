<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Button, Card, EmptyState, IconButton, Modal, PageHeader } from '@/Components/UI';
import {
    ArrowDownTrayIcon, ArrowPathIcon, TrashIcon, ClipboardDocumentIcon, ClipboardDocumentCheckIcon, DocumentIcon,
    QrCodeIcon, ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    certificate: Object,
});

const toast = useToast();

const breadcrumbs = computed(() => [
    { label: 'Sertifikatlar', href: route('admin.certificates.index') },
    { label: props.certificate.code },
]);

// Copy verification link
const copied = ref(false);
const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(props.certificate.verification_url || '');
        copied.value = true;
        toast.success('Havola nusxalandi');
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        toast.error('Nusxalab bo\'lmadi');
    }
};

// Regenerate
const regenOpen = ref(false);
const regenLoading = ref(false);
const regenerate = () => {
    router.post(route('admin.certificates.regenerate', props.certificate.id), {}, {
        preserveScroll: true,
        onStart: () => (regenLoading.value = true),
        onFinish: () => {
            regenLoading.value = false;
            regenOpen.value = false;
        },
    });
};

// Delete — the endpoint redirects "back" to this (now missing) page, so land on the list instead
const deleteOpen = ref(false);
const deleting = ref(false);
const destroy = () => {
    const off = router.on('invalid', (event) => {
        if (event.detail?.response?.status === 404) {
            event.preventDefault();
            toast.success('Sertifikat o\'chirildi');
            router.visit(route('admin.certificates.index'));
        }
    });
    router.delete(route('admin.certificates.destroy', props.certificate.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            off();
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head :title="`Sertifikat ${certificate.code}`" />

        <PageHeader :breadcrumbs="breadcrumbs">
            <template #title>
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-fg">{{ certificate.code }}</h1>
            </template>
            <template #meta>
                <span class="text-13 text-fg-subtle">{{ certificate.student?.name }} · {{ certificate.course?.title }}</span>
            </template>
            <template #actions>
                <Button v-if="certificate.pdf_url" :icon-left="ArrowDownTrayIcon" external
                    :href="route('admin.certificates.download', certificate.id)">
                    Yuklab olish
                </Button>
                <Button variant="secondary" :icon-left="ArrowPathIcon" @click="regenOpen = true">Qayta generatsiya</Button>
                <IconButton :icon="TrashIcon" label="O'chirish" variant="danger" @click="deleteOpen = true" />
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card padding="none" title="PDF ko'rinishi">
                    <template v-if="certificate.pdf_url" #actions>
                        <Button size="sm" variant="ghost" :icon-right="ArrowTopRightOnSquareIcon" external :href="certificate.pdf_url">
                            Yangi oynada
                        </Button>
                    </template>
                    <div class="p-4 sm:p-5">
                        <div class="relative aspect-[1.414] w-full overflow-hidden rounded-md border border-border bg-surface-2">
                            <iframe v-if="certificate.pdf_url" :src="certificate.pdf_url" title="Sertifikat PDF"
                                class="h-full w-full" />
                            <EmptyState v-else :icon="DocumentIcon" title="PDF mavjud emas"
                                description="Faylni yaratish uchun “Qayta generatsiya” tugmasini bosing" size="sm"
                                class="absolute inset-0 flex flex-col items-center justify-center" />
                        </div>
                    </div>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="O'quvchi">
                    <div class="flex items-center gap-3">
                        <Avatar :name="certificate.student?.name" size="md" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-fg">{{ certificate.student?.name }}</p>
                            <p v-if="certificate.student?.email" class="truncate text-13 text-fg-subtle">{{ certificate.student.email }}</p>
                            <p v-if="certificate.student?.phone" class="text-13 tabular-nums text-fg-subtle">{{ certificate.student.phone }}</p>
                        </div>
                    </div>
                </Card>

                <Card title="Sertifikat">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Kurs</dt>
                            <dd class="text-right font-medium">{{ certificate.course?.title }}</dd>
                        </div>
                        <div v-if="certificate.course?.direction" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Yo'nalish</dt>
                            <dd class="text-right font-medium">{{ certificate.course.direction }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Shablon</dt>
                            <dd class="text-right font-medium">{{ certificate.template_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Berilgan sana</dt>
                            <dd class="text-right font-medium tabular-nums">{{ certificate.issued_at || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tekshirilgan</dt>
                            <dd class="text-right font-medium tabular-nums">{{ certificate.verification_count || 0 }} marta</dd>
                        </div>
                    </dl>

                    <div v-if="certificate.verification_url" class="mt-4 border-t border-border pt-4">
                        <p class="mb-1.5 text-13 font-medium text-fg">Tekshirish havolasi</p>
                        <div class="flex items-center gap-2 rounded-md border border-border bg-surface-2 py-1 pl-3 pr-1">
                            <span class="min-w-0 flex-1 truncate font-mono text-xs text-fg-muted" :title="certificate.verification_url">
                                {{ certificate.verification_url }}
                            </span>
                            <IconButton :icon="copied ? ClipboardDocumentCheckIcon : ClipboardDocumentIcon" size="sm"
                                :label="copied ? 'Nusxalandi' : 'Havolani nusxalash'" @click="copyLink" />
                        </div>
                    </div>
                </Card>

                <Card title="QR kod">
                    <div class="flex justify-center">
                        <img v-if="certificate.qr_code_url" :src="certificate.qr_code_url" alt="Sertifikatni tekshirish QR kodi"
                            class="h-44 w-44 rounded-md border border-border bg-surface p-2" />
                        <div v-else class="flex h-44 w-44 flex-col items-center justify-center gap-2 rounded-md bg-surface-2 text-fg-subtle">
                            <QrCodeIcon class="h-8 w-8" aria-hidden="true" />
                            <span class="text-13">QR mavjud emas</span>
                        </div>
                    </div>
                </Card>
            </aside>
        </div>

        <Modal :show="regenOpen" size="sm" title="Qayta generatsiya" @close="regenOpen = false">
            <p class="text-sm text-fg-muted">PDF fayl joriy shablon asosida qaytadan yaratiladi. Eski fayl almashtiriladi.</p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="regenLoading" @click="regenOpen = false">Bekor qilish</Button>
                    <Button :icon-left="ArrowPathIcon" :loading="regenLoading" @click="regenerate">Generatsiya qilish</Button>
                </div>
            </template>
        </Modal>

        <Modal :show="deleteOpen" size="sm" title="Sertifikatni o'chirish" @close="deleteOpen = false">
            <p class="text-sm text-fg-muted">
                <span class="font-mono font-medium text-fg">{{ certificate.code }}</span> sertifikati, PDF va QR fayllari butunlay
                o'chiriladi. Bu amalni qaytarib bo'lmaydi.
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :disabled="deleting" @click="deleteOpen = false">Bekor qilish</Button>
                    <Button variant="danger" :icon-left="TrashIcon" :loading="deleting" @click="destroy">O'chirish</Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>
