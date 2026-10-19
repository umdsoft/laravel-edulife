<script setup>
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { EllipsisVerticalIcon, PencilSquareIcon, TrashIcon, BookmarkIcon, BookmarkSlashIcon } from '@heroicons/vue/24/outline';
import { BookmarkIcon as BookmarkSolidIcon, ClockIcon } from '@heroicons/vue/20/solid';
import Modal from '@/Components/UI/Modal.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { ANNOUNCEMENT_TYPES, announcementType, formatDateTime } from './format';

const props = defineProps({
    announcement: Object,
    course: Object,
});

const showEditModal = ref(false);
const editForm = useForm({
    title: props.announcement.title,
    content: props.announcement.content,
    type: props.announcement.type,
    is_pinned: props.announcement.is_pinned,
});

const type = computed(() => announcementType(props.announcement.type));
// published_at is when students see it (may be in the future); older rows may lack it
const shownDate = computed(() => props.announcement.published_at || props.announcement.created_at);
const isScheduled = computed(() => !!props.announcement.published_at && new Date(props.announcement.published_at) > new Date());

const togglePin = () => {
    router.post(route('teacher.courses.announcements.toggle-pin', [props.course.id, props.announcement.id]), {}, { preserveScroll: true });
};

const confirming = ref(false);
const deleting = ref(false);
const deleteAnnouncement = () => {
    router.delete(route('teacher.announcements.destroy', props.announcement.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = false;
        },
    });
};

const openEdit = () => {
    editForm.title = props.announcement.title;
    editForm.content = props.announcement.content;
    editForm.type = props.announcement.type;
    editForm.is_pinned = props.announcement.is_pinned;
    editForm.clearErrors();
    showEditModal.value = true;
};

const updateAnnouncement = () => {
    editForm.put(route('teacher.announcements.update', props.announcement.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const formId = computed(() => `announcement-edit-${props.announcement.id}`);
</script>

<template>
    <article :class="['rounded-lg border bg-surface p-5 shadow-xs sm:p-6', announcement.is_pinned ? 'border-brand-200' : 'border-border']">
        <header class="flex items-start gap-3">
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <Badge :variant="type.variant">{{ type.label }}</Badge>
                <Badge v-if="announcement.is_pinned" variant="brand" :icon="BookmarkSolidIcon">Qadalgan</Badge>
                <Badge v-if="isScheduled" variant="warning" :icon="ClockIcon">Rejalashtirilgan</Badge>
                <time :datetime="shownDate" class="text-13 tabular-nums text-fg-subtle">
                    {{ formatDateTime(shownDate) }}
                </time>
            </div>
            <Dropdown align="right">
                <template #trigger>
                    <IconButton :icon="EllipsisVerticalIcon" :label="`${announcement.title} — amallar`" size="sm" class="-mr-2 -mt-1" />
                </template>
                <DropdownItem :icon="announcement.is_pinned ? BookmarkSlashIcon : BookmarkIcon" @click="togglePin">
                    {{ announcement.is_pinned ? 'Qadashni bekor qilish' : 'Yuqoriga qadash' }}
                </DropdownItem>
                <DropdownItem :icon="PencilSquareIcon" @click="openEdit">Tahrirlash</DropdownItem>
                <DropdownItem divider />
                <DropdownItem danger :icon="TrashIcon" @click="confirming = true">O'chirish</DropdownItem>
            </Dropdown>
        </header>

        <h3 class="mt-3 text-base font-semibold text-fg">{{ announcement.title }}</h3>
        <div class="mt-1.5 whitespace-pre-wrap text-sm leading-6 text-fg-muted">{{ announcement.content }}</div>

        <!-- Edit modal -->
        <Modal :show="showEditModal" title="E'lonni tahrirlash" size="lg" @close="showEditModal = false">
            <form :id="formId" class="space-y-5 pb-2" @submit.prevent="updateAnnouncement">
                <Input v-model="editForm.title" label="Sarlavha" required maxlength="255" :error="editForm.errors.title" />
                <Select v-model="editForm.type" label="Turi" :options="ANNOUNCEMENT_TYPES" :error="editForm.errors.type" />
                <Textarea v-model="editForm.content" label="Matn" required :rows="6" autoresize maxlength="10000"
                    hint="Kamida 20 belgi" :error="editForm.errors.content" />
                <Checkbox v-model="editForm.is_pinned" label="Yuqoriga qadash" />
            </form>
            <template #footer>
                <Button variant="secondary" @click="showEditModal = false">Bekor qilish</Button>
                <Button type="submit" :form="formId" :loading="editForm.processing">Saqlash</Button>
            </template>
        </Modal>

        <ConfirmDialog :show="confirming" title="E'lonni o'chirasizmi?" :description="`«${announcement.title}» o'chiriladi.`"
            :loading="deleting" @confirm="deleteAnnouncement" @close="confirming = false" />
    </article>
</template>
