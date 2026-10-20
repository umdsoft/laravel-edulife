/**
 * Headless test harness for the virtual-lab simulation SFCs.
 *
 * Compiles a Simulations/*.vue file with @vue/compiler-sfc (no Vite plugin needed),
 * mounts it with a minimal custom Vue renderer (fake elements, a no-op 2-D canvas
 * context) and a fake animation clock, and gives the test direct access to the
 * component's <script setup> bindings:
 *
 *   const sim = await mountSimulation('PendulumSimulation', { config });
 *   sim.set({ length: 0.5 });          // writes refs, flushes watchers
 *   sim.call('start');                 // calls a setup function
 *   await sim.advance(20);             // runs 20 s of requestAnimationFrame frames
 *   sim.call('measure');
 *   sim.measurements                   // every emitted 'measurement' payload
 *   sim.text()                         // all rendered text (template output)
 *
 * The simulations run exactly as in the browser: same physics modules, same
 * template expressions, real elapsed-time animation (60 fps by default).
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { parse, compileScript, compileTemplate } from '@vue/compiler-sfc';
import * as Vue from 'vue';

const SIM_DIR = new URL('../', import.meta.url);

// ─────────────────────────── fake DOM / canvas ───────────────────────────

const noop = () => {};
/** A 2-D context whose every method is a no-op (getters return harmless values). */
function fakeContext2d() {
    const gradient = { addColorStop: noop };
    const base = {
        measureText: (s) => ({ width: String(s ?? '').length * 6 }),
        createLinearGradient: () => gradient,
        createRadialGradient: () => gradient,
        createConicGradient: () => gradient,
        createPattern: () => null,
        getImageData: (x, y, w, h) => ({ data: new Uint8ClampedArray(Math.max(0, w * h * 4)), width: w, height: h }),
        createImageData: (w, h) => ({ data: new Uint8ClampedArray(Math.max(0, w * h * 4)), width: w, height: h }),
        getTransform: () => ({ a: 1, b: 0, c: 0, d: 1, e: 0, f: 0 }),
        isPointInPath: () => false,
        getLineDash: () => [],
    };
    return new Proxy(base, {
        get(target, key) {
            if (key in target) return target[key];
            return noop;
        },
        set(target, key, value) {
            target[key] = value;
            return true;
        },
    });
}

class FakeNode {
    constructor(type, tag = '') {
        this.nodeType = type;
        this.tag = tag;
        this.children = [];
        this.parentNode = null;
        this.text = '';
        this.props = {};
        this.handlers = {};
        this.style = {};
        this.value = '';
        this.options = [];
        this.width = 800;
        this.height = 600;
        this.composing = false;
    }

    addEventListener(name, fn) {
        (this.handlers[`__${name}`] ||= []).push(fn);
    }

    removeEventListener(name, fn) {
        const list = this.handlers[`__${name}`] || [];
        this.handlers[`__${name}`] = list.filter((f) => f !== fn);
    }

    dispatch(name, extra = {}) {
        const ev = { type: name, target: this, currentTarget: this, preventDefault: noop, stopPropagation: noop, ...extra };
        for (const fn of this.handlers[`__${name}`] || []) fn(ev);
        const on = this.handlers[`on${name[0].toUpperCase()}${name.slice(1)}`];
        if (typeof on === 'function') on(ev);
        else if (Array.isArray(on)) on.forEach((f) => f(ev));
    }

    getContext() {
        return (this.__ctx ||= fakeContext2d());
    }

    getBoundingClientRect() {
        return { left: 0, top: 0, right: this.width, bottom: this.height, width: this.width, height: this.height, x: 0, y: 0 };
    }

    getRootNode() {
        return { activeElement: null };
    }

    setAttribute(k, v) {
        this.props[k] = v;
    }

    removeAttribute(k) {
        delete this.props[k];
    }

    get textContent() {
        if (this.nodeType === 3) return this.text;
        return this.children.map((c) => c.textContent).join('');
    }

    set textContent(v) {
        this.children = [];
        if (v) {
            const t = new FakeNode(3);
            t.text = String(v);
            t.parentNode = this;
            this.children.push(t);
        }
    }
}

const nodeOps = {
    createElement: (tag) => new FakeNode(1, tag),
    createText: (text) => Object.assign(new FakeNode(3), { text }),
    createComment: (text) => Object.assign(new FakeNode(8), { text }),
    setText: (node, text) => {
        node.text = text;
    },
    setElementText: (el, text) => {
        el.textContent = text;
    },
    insert: (child, parent, anchor) => {
        if (child.parentNode) nodeOps.remove(child);
        const i = anchor ? parent.children.indexOf(anchor) : -1;
        if (i >= 0) parent.children.splice(i, 0, child);
        else parent.children.push(child);
        child.parentNode = parent;
    },
    remove: (child) => {
        const p = child.parentNode;
        if (p) {
            const i = p.children.indexOf(child);
            if (i >= 0) p.children.splice(i, 1);
        }
        child.parentNode = null;
    },
    parentNode: (node) => node.parentNode,
    nextSibling: (node) => {
        const p = node.parentNode;
        if (!p) return null;
        return p.children[p.children.indexOf(node) + 1] ?? null;
    },
    querySelector: () => null,
    setScopeId: noop,
    cloneNode: (n) => n,
    insertStaticContent: (content, parent, anchor) => {
        const t = new FakeNode(3);
        t.text = content.replace(/<[^>]*>/g, ' ');
        nodeOps.insert(t, parent, anchor);
        return [t, t];
    },
};

function patchProp(el, key, prev, next) {
    if (/^on[A-Z]/.test(key)) {
        el.handlers[key] = next;
        return;
    }
    if (key === 'value') el.value = next;
    else if (key === 'checked') el.checked = next;
    else if (key === 'style') el.style = next || {};
    else if (key === 'width' || key === 'height') el[key] = Number(next);
    el.props[key] = next;
}

const renderer = Vue.createRenderer({ ...nodeOps, patchProp });

// ─────────────────────────── fake clock ───────────────────────────

function installClock() {
    const clock = { now: 1000, queue: new Map(), nextId: 1 };
    const saved = {
        raf: globalThis.requestAnimationFrame,
        caf: globalThis.cancelAnimationFrame,
        perfNow: globalThis.performance.now,
        document: globalThis.document,
        window: globalThis.window,
    };
    globalThis.requestAnimationFrame = (cb) => {
        const id = clock.nextId++;
        clock.queue.set(id, cb);
        return id;
    };
    globalThis.cancelAnimationFrame = (id) => {
        clock.queue.delete(id);
    };
    globalThis.performance.now = () => clock.now;
    if (!globalThis.document) {
        globalThis.document = {
            createElement: (tag) => new FakeNode(1, tag),
            activeElement: null,
            addEventListener: noop,
            removeEventListener: noop,
        };
    }
    if (!globalThis.window) {
        globalThis.window = { addEventListener: noop, removeEventListener: noop, devicePixelRatio: 1 };
    }
    clock.restore = () => {
        globalThis.requestAnimationFrame = saved.raf;
        globalThis.cancelAnimationFrame = saved.caf;
        globalThis.performance.now = saved.perfNow;
        globalThis.document = saved.document;
        globalThis.window = saved.window;
    };
    return clock;
}

// ─────────────────────────── SFC compilation ───────────────────────────

const moduleCache = new Map();

/** Turns `import { a, b as c } from 'x'` / `import d from 'x'` into lookups in __mods. */
function rewriteModule(code) {
    const specs = new Set();
    const imported = (spec) => {
        specs.add(spec);
        return `__mods[${JSON.stringify(spec)}]`;
    };
    let out = code.replace(/import\s+\{([^}]*)\}\s+from\s+['"]([^'"]+)['"];?/g, (_m, names, spec) => {
        const fields = names.split(',').map((s) => s.trim()).filter(Boolean)
            .map((s) => s.replace(/\s+as\s+/, ': '));
        return `const { ${fields.join(', ')} } = ${imported(spec)};`;
    });
    out = out.replace(/import\s+\*\s+as\s+(\w+)\s+from\s+['"]([^'"]+)['"];?/g, (_m, name, spec) => `const ${name} = ${imported(spec)};`);
    out = out.replace(/import\s+(\w+)\s+from\s+['"]([^'"]+)['"];?/g, (_m, name, spec) => `const ${name} = ${imported(spec)}.default;`);
    return { code: out, specs: [...specs] };
}

async function loadModules(specs) {
    const mods = {};
    for (const spec of specs) {
        if (spec === 'vue') mods[spec] = Vue;
        else if (spec.startsWith('.')) mods[spec] = await import(/* @vite-ignore */ new URL(spec, SIM_DIR).href);
        else mods[spec] = await import(/* @vite-ignore */ spec);
    }
    return mods;
}

/** Compiles Simulations/<name>.vue into a component options object (cached). */
export async function loadSimulationComponent(name) {
    if (moduleCache.has(name)) return moduleCache.get(name);
    const file = fileURLToPath(new URL(`${name}.vue`, SIM_DIR));
    const source = readFileSync(file, 'utf8');
    const { descriptor, errors } = parse(source, { filename: file });
    if (errors.length) throw new Error(`${name}.vue: ${errors[0].message}`);
    const id = `t-${name}`;
    const script = compileScript(descriptor, { id, inlineTemplate: false, genDefaultAs: '__sfc__' });
    const tpl = compileTemplate({
        source: descriptor.template.content,
        filename: file,
        id,
        compilerOptions: { bindingMetadata: script.bindings, mode: 'module' },
    });
    if (tpl.errors.length) throw new Error(`${name}.vue template: ${tpl.errors[0]}`);

    const s = rewriteModule(script.content.replace(/export\s+default\s+__sfc__;?/, ''));
    const t = rewriteModule(tpl.code.replace(/export\s+function\s+render/, 'function render'));
    const mods = await loadModules([...new Set([...s.specs, ...t.specs])]);
    // eslint-disable-next-line no-new-func
    const factory = new Function('__mods', `${s.code}\n${t.code}\n__sfc__.render = render;\nreturn __sfc__;`);
    const component = factory(mods);
    component.__file = pathToFileURL(file).href;
    moduleCache.set(name, component);
    return component;
}

// ─────────────────────────── mounting ───────────────────────────

/**
 * Mounts a simulation. Options: config (simulation_config), currentTask, props, fps.
 * Returns a handle: { setup, set, get, call, advance, measurements, text, unmount }.
 */
export async function mountSimulation(name, { config = {}, currentTask = null, props = {}, fps = 60 } = {}) {
    const component = await loadSimulationComponent(name);
    const clock = installClock();
    const measurements = [];
    const events = [];
    const warnings = [];
    const errors = [];
    const root = new FakeNode(1, 'root');
    const app = renderer.createApp(component, {
        config,
        currentTask,
        isPaused: false,
        state: {},
        ...props,
        onMeasurement: (m) => measurements.push(m),
        'onUpdate:state': (s) => events.push(['update:state', s]),
        onTaskComplete: (s) => events.push(['taskComplete', s]),
    });
    app.config.warnHandler = (msg) => warnings.push(msg);
    app.config.errorHandler = (err) => errors.push(err);
    app.mount(root);
    await Vue.nextTick();
    const instance = root._vnode?.component ?? app._instance;
    const setup = instance.setupState;

    const flush = async () => {
        await Vue.nextTick();
        await Vue.nextTick();
    };

    const runFrame = () => {
        clock.now += 1000 / fps;
        const cbs = [...clock.queue.values()];
        clock.queue.clear();
        for (const cb of cbs) cb(clock.now);
    };

    const handle = {
        instance,
        setup,
        measurements,
        events,
        warnings,
        errors,
        root,
        clock,
        /** Assigns setup bindings (refs are unwrapped by setupState) and flushes watchers. */
        async set(values) {
            for (const [k, v] of Object.entries(values)) {
                if (!(k in setup)) throw new Error(`${name}: no setup binding "${k}"`);
                setup[k] = v;
            }
            await flush();
        },
        get(key) {
            if (!(key in setup)) throw new Error(`${name}: no setup binding "${key}"`);
            return setup[key];
        },
        call(fn, ...args) {
            if (typeof setup[fn] !== 'function') throw new Error(`${name}: no setup function "${fn}"`);
            return setup[fn](...args);
        },
        /** Runs `seconds` of animation frames (frame-rate `fps`), flushing reactivity every frame. */
        async advance(seconds) {
            const frames = Math.round(seconds * fps);
            for (let i = 0; i < frames; i++) {
                runFrame();
                // eslint-disable-next-line no-await-in-loop
                if (i % 10 === 9 || i === frames - 1) await flush();
            }
            await flush();
        },
        async flush() {
            await flush();
        },
        /** Sets new props (e.g. currentTask, isPaused) and re-renders. */
        async setProps(next) {
            Object.assign(instance.props, next);
            await flush();
        },
        /** All rendered text, whitespace-collapsed. */
        text() {
            return root.textContent.replace(/\s+/g, ' ').trim();
        },
        /** Last emitted measurement whose name starts with `prefix`. */
        last(prefix) {
            const norm = (s) => String(s).toLowerCase().replace(/[’'ʻʼ`]/g, "'");
            return [...measurements].reverse().find((m) => norm(m.name).startsWith(norm(prefix)));
        },
        unmount() {
            try {
                app.unmount();
            } finally {
                clock.restore();
            }
        },
    };
    return handle;
}
