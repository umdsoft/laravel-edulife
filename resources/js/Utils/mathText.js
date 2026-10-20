/**
 * Short teacher text (a test question, an answer option, an explanation) → HTML
 * with its $…$ / $$…$$ / \(…\) / \[…\] formulas rendered by KaTeX.
 *
 * Unlike richText.js there is no Markdown: the text is shown as typed (line breaks kept by
 * the caller's `whitespace-pre-wrap`). Plain text is HTML-escaped; text that already is
 * HTML goes through `sanitizeHtml` (pass DOMPurify in the browser) before the formulas are
 * put back, because KaTeX output uses inline styles a sanitizer would strip.
 */
import { extractMath, injectMath, looksLikeHtml } from './richText';

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ESCAPES[c]);

/** True when the text has at least one formula delimiter pair. */
export function hasMath(text) {
    return extractMath(String(text ?? '')).formulas.length > 0;
}

export function mathTextHtml(text, sanitizeHtml = null) {
    const { text: withTokens, formulas } = extractMath(String(text ?? ''));
    const safe = looksLikeHtml(withTokens) && sanitizeHtml ? sanitizeHtml(withTokens) : escapeHtml(withTokens);

    return injectMath(safe, formulas);
}
