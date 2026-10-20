/**
 * Upload a question image (POST media.question-images.store) with progress.
 *   const { uploading, progress, error, upload } = useImageUpload();
 *   const { url, markdown } = await upload(file);     // null on failure (error.value has the reason)
 * The server checks the type (png/jpg/webp/gif, no SVG) and size (≤ 5 MB), converts to WebP ≤ 1600 px.
 */
import { ref } from 'vue';
import axios from 'axios';

export const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
export const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

/** Client-side pre-check (the server checks again); null when fine, else the Uzbek reason. */
export function imageFileError(file) {
    if (!file) return 'Fayl tanlanmadi.';
    if (!IMAGE_TYPES.includes(file.type)) return 'Faqat PNG, JPG, WebP yoki GIF rasm yuklash mumkin (SVG qabul qilinmaydi).';
    if (file.size > MAX_IMAGE_BYTES) return 'Rasm hajmi 5 MB dan oshmasin.';
    return null;
}

/** Alt text from a file name: "uchburchak_abc.png" → "uchburchak abc". */
export function altFromFileName(name) {
    const base = String(name || '').replace(/\.[a-z0-9]+$/i, '').replace(/[_-]+/g, ' ').trim();
    return !base || /^(image|img|screenshot|rasm|photo|scan)\s*\d*$/i.test(base) ? 'chizma' : base.slice(0, 80);
}

export function useImageUpload() {
    const uploading = ref(false);
    const progress = ref(0);
    const error = ref('');

    const upload = async (file) => {
        error.value = '';
        const pre = imageFileError(file);
        if (pre) {
            error.value = pre;
            return null;
        }
        uploading.value = true;
        progress.value = 0;
        try {
            const body = new FormData();
            body.append('image', file);
            body.append('alt', altFromFileName(file.name));
            const { data } = await axios.post(route('media.question-images.store'), body, {
                headers: { 'Content-Type': 'multipart/form-data', Accept: 'application/json' },
                onUploadProgress: (e) => {
                    if (e.total) progress.value = Math.round((e.loaded / e.total) * 100);
                },
            });
            return data;
        } catch (e) {
            const res = e?.response;
            error.value = res?.data?.errors?.image?.[0]
                || res?.data?.message
                || (res?.status === 429 ? "Juda ko'p yuklash. Bir daqiqadan keyin urinib ko'ring." : "Rasmni yuklab bo'lmadi. Internetni tekshirib, qayta urinib ko'ring.");
            return null;
        } finally {
            uploading.value = false;
        }
    };

    return { uploading, progress, error, upload };
}
