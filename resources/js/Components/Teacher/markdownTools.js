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
