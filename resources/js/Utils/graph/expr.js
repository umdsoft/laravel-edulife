/**
 * A small, safe math expression parser for graph functions (no eval / Function).
 *
 *   const f = compileExpr('2x^2 - sin(pi*x)/3');   // throws ExprError on bad input
 *   f(1.5) // → number (NaN outside the domain, e.g. sqrt(-1))
 *
 * Grammar (the PHP twin is App\Support\RichText\GraphExpression — keep them in sync):
 *   expr    := term (('+' | '-') term)*
 *   term    := unary (('*' | '/') unary | <implicit ×> unary)*     2x, 3(x+1), (x-1)(x+1), 2sin(x)
 *   unary   := ('-' | '+') unary | power
 *   power   := primary ('^' unary)?                                 right-associative, -x^2 = -(x^2)
 *   primary := number | x | t | pi | π | e | func '(' expr (',' expr)? ')' | '(' expr ')' | '|' expr '|'
 */

export class ExprError extends Error {}

export const MAX_EXPR_LENGTH = 200;
const MAX_DEPTH = 40;

const FN1 = {
    sin: Math.sin, cos: Math.cos, tan: Math.tan,
    cot: (v) => 1 / Math.tan(v),
    asin: Math.asin, acos: Math.acos, atan: Math.atan,
    arcsin: Math.asin, arccos: Math.acos, arctan: Math.atan,
    sinh: Math.sinh, cosh: Math.cosh, tanh: Math.tanh,
    sqrt: Math.sqrt, cbrt: Math.cbrt, abs: Math.abs,
    ln: Math.log, log: Math.log10, lg: Math.log10, log2: Math.log2,
    exp: Math.exp, floor: Math.floor, ceil: Math.ceil, round: Math.round, sign: Math.sign,
};
const FN2 = { min: Math.min, max: Math.max };
const CONSTANTS = { pi: Math.PI, 'π': Math.PI, e: Math.E };
const VARIABLES = ['x', 't'];

/** Names a teacher may use (shown in the editor's help). */
export const ALLOWED_FUNCTIONS = [...Object.keys(FN1), ...Object.keys(FN2)];

export function tokenize(src) {
    const s = String(src ?? '');
    if (!s.trim()) throw new ExprError("Ifoda bo'sh");
    if (s.length > MAX_EXPR_LENGTH) throw new ExprError(`Ifoda juda uzun (ko'pi bilan ${MAX_EXPR_LENGTH} belgi)`);

    const tokens = [];
    let i = 0;
    while (i < s.length) {
        const c = s[i];
        if (/\s/.test(c)) { i++; continue; }
        if (/[0-9.]/.test(c)) {
            const m = /^(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/.exec(s.slice(i));
            if (!m) throw new ExprError(`Noto'g'ri son: "${s.slice(i, i + 8)}"`);
            // "2e" followed by a non-digit is 2·e, so only a full exponent counts
            tokens.push({ type: 'num', value: parseFloat(m[0]) });
            i += m[0].length;
            continue;
        }
        if (/[a-zA-Zπ]/.test(c)) {
            const m = /^(π|[a-zA-Z][a-zA-Z0-9]*)/.exec(s.slice(i));
            tokens.push({ type: 'id', value: m[0].toLowerCase() });
            i += m[0].length;
            continue;
        }
        if ('+-*/^(),|'.includes(c)) { tokens.push({ type: c }); i++; continue; }
        if (c === '·' || c === '×') { tokens.push({ type: '*' }); i++; continue; }
        if (c === '−') { tokens.push({ type: '-' }); i++; continue; }
        throw new ExprError(`Ruxsat etilmagan belgi: "${c}"`);
    }

    return splitIdentifiers(tokens);
}

/** "xsin" / "pix" / "2pix" style runs: split an unknown identifier into known names (x, t, pi, e, functions). */
function splitIdentifiers(tokens) {
    const known = [...Object.keys(FN1), ...Object.keys(FN2), ...Object.keys(CONSTANTS), ...VARIABLES]
        .sort((a, b) => b.length - a.length);
    const out = [];
    for (const tok of tokens) {
        if (tok.type !== 'id' || isKnown(tok.value)) { out.push(tok); continue; }
        let rest = tok.value;
        while (rest) {
            const name = known.find((k) => rest.startsWith(k));
            const digits = name ? null : /^\d+/.exec(rest);
            if (!name && !digits) throw new ExprError(`Noma'lum nom: "${tok.value}"`);
            out.push(name ? { type: 'id', value: name } : { type: 'num', value: Number(digits[0]) });
            rest = rest.slice(name ? name.length : digits[0].length);
        }
    }

    return out;
}

const own = (obj, key) => Object.prototype.hasOwnProperty.call(obj, key); // never the prototype ("constructor")
const isKnown = (name) => own(FN1, name) || own(FN2, name) || own(CONSTANTS, name) || VARIABLES.includes(name);

/** Source → AST. Nodes: {n:'num',v} {n:'var'} {n:'neg',a} {n:'bin',op,a,b} {n:'fn',f,args} */
export function parseExpr(src) {
    const tokens = tokenize(src);
    let pos = 0;
    let depth = 0;
    let absDepth = 0; // inside |…| a bar closes, outside it opens
    const peek = () => tokens[pos];
    const next = () => tokens[pos++];
    const expect = (type) => {
        const t = next();
        if (!t || t.type !== type) throw new ExprError(type === ')' ? "Qavs yopilmagan" : `"${type}" kutilgan edi`);
    };
    const enter = () => { if (++depth > MAX_DEPTH) throw new ExprError('Ifoda juda chuqur'); };
    const leave = () => { depth--; };

    const startsPrimary = (t) => t && (t.type === 'num' || t.type === 'id' || t.type === '(' || (t.type === '|' && absDepth === 0));

    function expr() {
        enter();
        let node = term();
        while (peek() && (peek().type === '+' || peek().type === '-')) {
            const op = next().type;
            node = { n: 'bin', op, a: node, b: term() };
        }
        leave();
        return node;
    }

    function term() {
        let node = unary();
        for (;;) {
            const t = peek();
            if (t && (t.type === '*' || t.type === '/')) {
                next();
                node = { n: 'bin', op: t.type, a: node, b: unary() };
            } else if (startsPrimary(t)) {
                node = { n: 'bin', op: '*', a: node, b: power() }; // implicit multiplication
            } else {
                return node;
            }
        }
    }

    function unary() {
        const t = peek();
        if (t && (t.type === '-' || t.type === '+')) {
            next();
            enter();
            const a = unary();
            leave();
            return t.type === '-' ? { n: 'neg', a } : a;
        }
        return power();
    }

    function power() {
        const base = primary();
        if (peek() && peek().type === '^') {
            next();
            enter();
            const exp = unary();
            leave();
            return { n: 'bin', op: '^', a: base, b: exp };
        }
        return base;
    }

    function primary() {
        const t = next();
        if (!t) throw new ExprError('Ifoda chala tugagan');
        if (t.type === 'num') return { n: 'num', v: t.value };
        if (t.type === '(') {
            const node = expr();
            expect(')');
            return node;
        }
        if (t.type === '|') {
            absDepth++;
            const node = expr();
            absDepth--;
            expect('|');
            return { n: 'fn', f: 'abs', args: [node] };
        }
        if (t.type === 'id') {
            if (VARIABLES.includes(t.value)) return { n: 'var' };
            if (own(CONSTANTS, t.value)) return { n: 'num', v: CONSTANTS[t.value] };
            if (own(FN1, t.value) || own(FN2, t.value)) {
                const arity = own(FN2, t.value) ? 2 : 1;
                if (!peek() || peek().type !== '(') throw new ExprError(`${t.value} dan keyin qavs kerak: ${t.value}(x)`);
                next();
                const args = [expr()];
                while (peek() && peek().type === ',') { next(); args.push(expr()); }
                expect(')');
                if (args.length !== arity) throw new ExprError(`${t.value} ${arity} ta argument oladi`);
                return { n: 'fn', f: t.value, args };
            }
        }
        throw new ExprError(`Kutilmagan belgi: "${t.value ?? t.type}"`);
    }

    const ast = expr();
    if (pos < tokens.length) {
        const t = tokens[pos];
        throw new ExprError(t.type === ')' ? "Ortiqcha yopuvchi qavs" : `Kutilmagan belgi: "${t.value ?? t.type}"`);
    }

    return ast;
}

function build(node) {
    switch (node.n) {
        case 'num': { const v = node.v; return () => v; }
        case 'var': return (x) => x;
        case 'neg': { const a = build(node.a); return (x) => -a(x); }
        case 'fn': {
            const args = node.args.map(build);
            if (args.length === 2) { const f = FN2[node.f]; const [a, b] = args; return (x) => f(a(x), b(x)); }
            const f = FN1[node.f]; const [a] = args; return (x) => f(a(x));
        }
        case 'bin': {
            const a = build(node.a);
            const b = build(node.b);
            switch (node.op) {
                case '+': return (x) => a(x) + b(x);
                case '-': return (x) => a(x) - b(x);
                case '*': return (x) => a(x) * b(x);
                case '/': return (x) => a(x) / b(x);
                case '^': return (x) => power(a(x), b(x));
            }
        }
    }
    throw new ExprError('Ichki xato');
}

/** Real power: (-8)^(1/3) = -2 (odd roots of negatives), otherwise Math.pow. */
function power(base, exp) {
    if (base < 0 && !Number.isInteger(exp)) {
        const inv = 1 / exp;
        if (Number.isInteger(Math.round(inv)) && Math.abs(inv - Math.round(inv)) < 1e-9 && Math.round(inv) % 2 !== 0) {
            return -Math.pow(-base, exp);
        }
    }
    return Math.pow(base, exp);
}

/** Source → (x) => number. Throws ExprError. */
export function compileExpr(src) {
    const fn = build(parseExpr(src));
    return (x) => {
        const y = fn(x);
        return typeof y === 'number' ? y : NaN;
    };
}

/** null when the expression is valid, else the (Uzbek) error message. */
export function exprError(src) {
    try {
        parseExpr(src);
        return null;
    } catch (e) {
        return e instanceof ExprError ? e.message : "Ifoda noto'g'ri";
    }
}
