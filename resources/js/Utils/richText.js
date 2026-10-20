/**
 * Teacher-authored text → HTML: Markdown (headings, lists, bold, links, …)
 * with LaTeX formulas ($…$ inline, $$…$$ display) rendered by KaTeX.
 *
 * Content that is already HTML (written with a rich-text editor) is left as
 * HTML; only its formulas are rendered. The caller must sanitize the
 * returned HTML (DOMPurify) *before* calling `injectMath()`, because KaTeX
 * output uses inline styles that the sanitizer would strip.
 */
import MarkdownIt from 'markdown-it';
import katex from 'katex';
import { parseGraphBlock } from './graph/spec';

const md = new MarkdownIt({
    html: false, // raw HTML in Markdown is escaped
    linkify: true,
    breaks: true, // a single newline is a line break (how teachers type)
});

const TOKEN = (i) => `@@MATH${i}@@`;

/** True when the text looks like HTML rather than Markdown / plain text. */
export function looksLikeHtml(text) {
    return /<\/?(p|div|br|ul|ol|li|h[1-6]|strong|em|b|i|span|table|img|blockquote|pre|code)\b[^>]*>/i.test(String(text || ''));
}

/**
 * Replace $$…$$ and $…$ with placeholders; returns { text, formulas }.
 * A "$" followed by a digit and preceded by a space-less digit is kept as money-like text only
 * when it has no closing pair on the same paragraph.
 */
export function extractMath(text) {
    const formulas = [];
    let out = String(text || '');

    out = out.replace(/\$\$([\s\S]+?)\$\$/g, (_, tex) => {
        formulas.push({ tex: tex.trim(), display: true });
        return TOKEN(formulas.length - 1);
    });
    out = out.replace(/\\\[([\s\S]+?)\\\]/g, (_, tex) => {
        formulas.push({ tex: tex.trim(), display: true });
        return TOKEN(formulas.length - 1);
    });
    out = out.replace(/(^|[^\\$])\$(?!\s)([^$\n]+?)(?<!\s)\$/g, (_, before, tex) => {
        formulas.push({ tex, display: false });
        return `${before}${TOKEN(formulas.length - 1)}`;
    });
    out = out.replace(/\\\((.+?)\\\)/g, (_, tex) => {
        formulas.push({ tex: tex.trim(), display: false });
        return TOKEN(formulas.length - 1);
    });

    return { text: out, formulas };
}

/** Markdown (or HTML) with formulas replaced by placeholders → HTML, plus the formulas. */
export function renderRichText(text) {
    const { text: withTokens, formulas } = extractMath(text);
    const html = looksLikeHtml(withTokens) ? withTokens : md.render(withTokens);

    return { html, formulas };
}

/** Put KaTeX HTML in place of the placeholders (call on sanitized HTML). */
export function injectMath(html, formulas) {
    return String(html || '').replace(/@@MATH(\d+)@@/g, (match, i) => {
        const f = formulas[Number(i)];
        if (!f) return match;
        try {
            return katex.renderToString(f.tex, { displayMode: f.display, throwOnError: false, output: 'html', trust: false });
        } catch {
            return f.tex.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[c]);
        }
    });
}

// ==================== media: images and graph blocks ====================
// Format (docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §1.1):
//   ![chizma](/storage/questions/2026/10/<uuid>.webp){width=60%}
//   ```graph
//   {"x":[-5,5],"y":[-3,9],"functions":[{"expr":"x^2-2"}]}
//   ```
// Only our own uploads render; any other image URL is dropped. The server mirror is
// App\Support\RichText\RichTextMedia (allow-list, per-field limits).


/** Uploaded question images: /storage/questions/YYYY/MM/<name>.<ext> (nothing else is rendered). */
export const ALLOWED_IMAGE_URL = /^\/storage\/questions\/\d{4}\/\d{2}\/[A-Za-z0-9_-]{1,80}\.(?:webp|png|jpe?g|gif)$/;

const IMAGE_RE = /!\[([^\]\n]{0,300})\]\(\s*<?([^)\s>]{1,500})>?(?:\s+"[^"\n]*")?\s*\)(?:\{\s*width\s*=\s*(\d{1,4})\s*(%|px)?\s*\})?/g;
const GRAPH_RE = /(^|\n)[ \t]*```graph[ \t]*\r?\n([\s\S]*?)\r?\n?[ \t]*```[ \t]*(?=\r?\n|$)/g;
const IMG_TOKEN = (i) => `@@IMG${i}@@`;

const ATTR_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ATTR_ESCAPES[c]);

export const isAllowedImageUrl = (url) => ALLOWED_IMAGE_URL.test(String(url || ''));

/** CSS width from the {width=…} suffix: 10–100 % or 40–1600 px; null when absent / out of range. */
export function imageWidth(value, unit) {
    const n = Number(value);
    if (!Number.isFinite(n) || value === undefined || value === null || value === '') return null;
    if (unit === 'px') return n >= 40 && n <= 1600 ? `${n}px` : null;
    return n >= 10 && n <= 100 ? `${n}%` : null;
}

/** Replace ![alt](url){width=…} with placeholders → { text, images: [{alt, url, width, allowed}] }. */
export function extractImages(text) {
    const images = [];
    const out = String(text ?? '').replace(IMAGE_RE, (_, alt, url, w, unit) => {
        images.push({ alt: alt.trim(), url, width: imageWidth(w, unit), allowed: isAllowedImageUrl(url) });
        return IMG_TOKEN(images.length - 1);
    });

    return { text: out, images };
}

/** Image HTML for one extracted image ('' when its URL is not ours). */
export function imageHtml(img, zoom = 'tap') {
    if (!img?.allowed) return '';
    const alt = escapeHtml(img.alt || 'Rasm');
    const style = img.width ? ` style="width:${img.width}"` : '';
    const tap = zoom === 'tap' ? ' tabindex="0" role="button" aria-haspopup="dialog"' : '';
    const badge = zoom === 'badge'
        ? '<span class="rt-zoom" role="button" tabindex="0" aria-label="Rasmni kattalashtirish" data-zoom="1">⤢</span>'
        : '';

    return `<span class="rt-img-wrap"${style}><img src="${escapeHtml(img.url)}" alt="${alt}" loading="lazy" decoding="async" class="rt-img"${zoom === 'tap' ? ' data-zoom="1"' : ''}${tap}>${badge}</span>`;
}

/** Put image HTML in place of the placeholders (call on sanitized HTML). */
export function injectImages(html, images, zoom = 'tap') {
    return String(html || '').replace(/@@IMG(\d+)@@/g, (match, i) => (images[Number(i)] ? imageHtml(images[Number(i)], zoom) : match));
}

/** Split text at ```graph blocks → [{type:'text', text} | {type:'graph', source, spec, errors}]. */
export function splitGraphBlocks(text) {
    const src = String(text ?? '');
    const parts = [];
    let last = 0;
    GRAPH_RE.lastIndex = 0;
    let m;
    while ((m = GRAPH_RE.exec(src)) !== null) {
        const start = m.index + m[1].length;
        if (start > last) parts.push({ type: 'text', text: src.slice(last, start) });
        const { spec, errors } = parseGraphBlock(m[2]);
        parts.push({ type: 'graph', source: m[2], spec, errors });
        last = GRAPH_RE.lastIndex;
    }
    if (last < src.length) parts.push({ type: 'text', text: src.slice(last) });

    return parts.filter((p) => p.type === 'graph' || p.text.trim() !== '' || parts.length === 1);
}

/**
 * Text → render segments: [{type:'html', html} | {type:'graph', spec, errors}].
 *   mode 'markdown' — blocks (lesson, exam stem, solution): Markdown + KaTeX + images
 *   mode 'plain'    — short text (test question, option): as typed, KaTeX + images
 * `sanitize` (DOMPurify in the browser) runs on the HTML before formulas and images are put back.
 */
export function richSegments(text, { mode = 'markdown', sanitize = null, zoom = 'tap' } = {}) {
    return splitGraphBlocks(text).map((part) => {
        if (part.type === 'graph') return part;
        const { text: noImages, images } = extractImages(part.text);
        const { text: withTokens, formulas } = extractMath(noImages);
        let html;
        if (mode === 'markdown') {
            html = looksLikeHtml(withTokens) ? withTokens : md.render(withTokens);
            html = sanitize ? sanitize(html) : html;
        } else {
            html = looksLikeHtml(withTokens) && sanitize ? sanitize(withTokens) : escapeHtml(withTokens);
        }
        if (mode === 'plain') html = html.replace(/^\n+|\n+$/g, '');

        return { type: 'html', html: injectImages(injectMath(html, formulas), images, zoom) };
    });
}

/** True when the text has an image (ours) or a graph block — options then render as a card grid. */
export function hasMedia(text) {
    const s = String(text ?? '');
    if (!s.includes('![') && !s.includes('```graph')) return false;
    return extractImages(s).images.some((i) => i.allowed) || splitGraphBlocks(s).some((p) => p.type === 'graph');
}

/** Text with images / graphs replaced by "[rasm]" / "[grafik]" (lists, previews, notifications). */
export function mediaPlaceholderText(text) {
    return splitGraphBlocks(text)
        .map((p) => (p.type === 'graph' ? '[grafik]' : p.text.replace(IMAGE_RE, '[rasm]')))
        .join(' ')
        .replace(/[ \t]{2,}/g, ' ')
        .trim();
}
