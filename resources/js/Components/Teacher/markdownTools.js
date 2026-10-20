/**
 * Text edits for the lesson editor toolbar. Pure functions: (text, selection) → new text + selection.
 */

/** Wrap the selection (or a placeholder) with `before` / `after`: **bold**, $formula$ … */
export function wrapSelection(text, start, end, before, after = before, placeholder = '') {
    const value = String(text ?? '');
    const selected = value.slice(start, end) || placeholder;
    const next = value.slice(0, start) + before + selected + after + value.slice(end);
    const selStart = start + before.length;

    return { text: next, start: selStart, end: selStart + selected.length };
}

/** Put `prefix` at the start of every selected line ("- " list, "## " heading, "1. "). */
export function prefixLines(text, start, end, prefix) {
    const value = String(text ?? '');
    const lineStart = value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;
    const block = value.slice(lineStart, end);
    const lines = block.split('\n');
    const numbered = prefix === '1. ';
    const replaced = lines.map((line, i) => (numbered ? `${i + 1}. ` : prefix) + line).join('\n');
    const next = value.slice(0, lineStart) + replaced + value.slice(end);

    return { text: next, start: lineStart, end: lineStart + replaced.length };
}

/** A display formula on its own lines. */
export function insertBlockFormula(text, start, end, placeholder = 'F = ma') {
    const value = String(text ?? '');
    const needsBreak = start > 0 && value[start - 1] !== '\n';
    const before = `${needsBreak ? '\n' : ''}$$`;

    return wrapSelection(value, start, end, before, '$$\n', placeholder);
}

// ==================== media (images, graph blocks) ====================

/** Insert `block` on its own lines at the cursor (replacing the selection); the cursor lands after it. */
export function insertBlock(text, start, end, block) {
    const value = String(text ?? '');
    const before = value.slice(0, start);
    const after = value.slice(end);
    const lead = before === '' || before.endsWith('\n') ? '' : '\n';
    const trail = after === '' || after.startsWith('\n') ? '' : '\n';
    const inserted = `${lead}${block}${trail}`;
    const caret = start + lead.length + block.length;

    return { text: before + inserted + after, start: caret, end: caret };
}

/** Inline snippet (an image) at the cursor, replacing the selection. */
export function insertInline(text, start, end, snippet) {
    const value = String(text ?? '');
    const caret = start + snippet.length;

    return { text: value.slice(0, start) + snippet + value.slice(end), start: caret, end: caret };
}

const GRAPH_BLOCK = /(^|\n)([ \t]*```graph[ \t]*\r?\n([\s\S]*?)\r?\n?[ \t]*```)[ \t]*(?=\r?\n|$)/g;

/** The ```graph block the cursor is in (or touches): { start, end, source } — null when none. */
export function graphBlockAt(text, pos) {
    const value = String(text ?? '');
    GRAPH_BLOCK.lastIndex = 0;
    let m;
    while ((m = GRAPH_BLOCK.exec(value)) !== null) {
        const start = m.index + m[1].length;
        const end = start + m[2].length;
        if (pos >= start && pos <= end) return { start, end, source: m[3] };
    }
    return null;
}

/** Markdown of a graph block for a spec JSON text. */
export const graphBlock = (json) => '```graph\n' + json + '\n```';
