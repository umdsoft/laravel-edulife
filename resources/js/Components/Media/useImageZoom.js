/**
 * Tap-to-zoom for images rendered by RichContent / MathText (event delegation on the container).
 *   const { zoomSrc, zoomAlt, onClick, onKeydown } = useImageZoom();
 *   <div @click="onClick" @keydown="onKeydown" v-html="…" />  <ImageZoom v-model:src="zoomSrc" :alt="zoomAlt" />
 * An image (or its ⤢ badge inside an option card) opens the zoom view and does not select the option.
 */
import { ref } from 'vue';

export function useImageZoom() {
    const zoomSrc = ref('');
    const zoomAlt = ref('');

    const open = (target, e) => {
        const trigger = target?.closest?.('[data-zoom]');
        if (!trigger) return false;
        const img = trigger.tagName === 'IMG' ? trigger : trigger.closest('.rt-img-wrap')?.querySelector('img');
        if (!img) return false;
        e.preventDefault();
        e.stopPropagation();
        zoomSrc.value = img.getAttribute('src') || '';
        zoomAlt.value = img.getAttribute('alt') || '';
        return true;
    };

    const onClick = (e) => open(e.target, e);
    const onKeydown = (e) => {
        if (e.key === 'Enter' || e.key === ' ') open(e.target, e);
    };

    return { zoomSrc, zoomAlt, onClick, onKeydown };
}
