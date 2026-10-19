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
