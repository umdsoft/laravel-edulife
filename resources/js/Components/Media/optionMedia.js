/**
 * Answer options with pictures or graphs render as a card grid: 2 columns, 1 under 360 px.
 *   :class="optionsHaveMedia(question.options) ? OPTION_GRID : 'space-y-2.5'"
 * An option counts when its text has an image / ```graph block or a legacy image column is set.
 */
import { hasMedia } from '@/Utils/richText';

export const OPTION_GRID = 'grid grid-cols-1 gap-2.5 min-[360px]:grid-cols-2';

export const optionText = (o) => (typeof o === 'string' ? o : String(o?.content ?? o?.text ?? o?.option_text ?? ''));

export const optionHasMedia = (o) => !!(o && (o.image || o.image_url || o.option_image || hasMedia(optionText(o))));

export const optionsHaveMedia = (options) => (options || []).some(optionHasMedia);
