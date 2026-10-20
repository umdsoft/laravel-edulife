/**
 * Measuring instruments of the virtual lab: resolution, accuracy class and
 * small, reproducible measurement scatter.
 *
 * A real school / university lab never reads the "exact" value: every reading is
 * rounded to the instrument's scale division (resolution) and carries a small
 * random error. The simulations model this here, in one tested place:
 *
 *  - `quantize(x, resolution)` — rounding to the scale division (no float dust:
 *    quantize(0.1 + 0.2, 0.01) === 0.3).
 *  - `createRng(seed)` — mulberry32, a tiny deterministic PRNG. A simulation seeds
 *    it from its own name + the parameter values (`seedFrom(...)`) so the n-th
 *    reading of a given set-up is ALWAYS the same number (reproducible lab sheets,
 *    reproducible tests), while repeated readings scatter like in a real lab.
 *  - `truncatedNormal(rng, limit)` — standard normal, truncated at ±limit·σ, so a
 *    reading can never fall outside the documented error band (the expected task
 *    answers stay checkable within their tolerance).
 *  - `reading(trueValue, spec, rng)` — trueValue + noise, then quantized.
 *  - `INSTRUMENTS` — the instrument catalog (resolution, σ, accuracy) used by the
 *    simulations; every entry is documented in docs/lab/PHYSICS_REFERENCES.md.
 *
 * Noise policy (see docs/lab/PHYSICS_REFERENCES.md, "O'lchov noaniqligi"):
 *  - σ is small and documented; |error| ≤ limit·σ + resolution/2 always;
 *  - for every seeded task: limit·σ + resolution/2 ≤ ½ · tolerance, so a correct
 *    procedure always passes the check;
 *  - formula-only views (computed values, calculator) never get noise.
 */

/** Number of decimals needed to print a multiple of `resolution` exactly (0.01 → 2, 5 → 0). */
export function decimalsOf(resolution) {
    if (!(resolution > 0) || !Number.isFinite(resolution)) return 0;
    for (let d = 0; d <= 12; d++) {
        const scaled = resolution * 10 ** d;
        const whole = Math.round(scaled);
        // relative test, and the scaled step must be ≥ 1 (1e-9 must not pass at d = 0)
        if (whole >= 1 && Math.abs(scaled - whole) < 1e-9 * scaled) return d;
    }
    return 12;
}

/**
 * Rounds `value` to the nearest multiple of `resolution` (half away from zero) and
 * removes binary floating-point dust. resolution ≤ 0 → value unchanged.
 * @param {number} value
 * @param {number} resolution scale division, same unit as value
 * @returns {number}
 */
export function quantize(value, resolution) {
    if (!Number.isFinite(value)) return value;
    if (!(resolution > 0)) return value;
    const n = Math.sign(value) * Math.round(Math.abs(value) / resolution + 1e-9);
    const q = n * resolution;
    return Number(q.toFixed(decimalsOf(resolution))) + 0; // + 0 turns −0 into 0
}

/** Formats a quantized reading with exactly the instrument's decimals ("2.00", "0.5", "450"). */
export function formatReading(value, resolution) {
    if (!Number.isFinite(value)) return '—';
    return quantize(value, resolution).toFixed(decimalsOf(resolution));
}

/**
 * Rounds to `sig` significant figures (for derived results shown to the student).
 * @returns {number}
 */
export function roundSig(value, sig = 3) {
    if (!Number.isFinite(value) || value === 0) return value;
    const p = Math.floor(Math.log10(Math.abs(value)));
    const f = 10 ** (sig - 1 - p);
    return Math.round(value * f) / f;
}

/**
 * 32-bit FNV-1a hash of any values (numbers are printed with 12 significant
 * digits so 0.1 + 0.2 and 0.3 give the same seed).
 * @returns {number} uint32 seed
 */
export function seedFrom(...parts) {
    const text = parts.map((p) => (typeof p === 'number' ? Number(p.toPrecision(12)).toString() : String(p))).join('|');
    let h = 0x811c9dc5;
    for (let i = 0; i < text.length; i++) {
        h ^= text.charCodeAt(i);
        h = Math.imul(h, 0x01000193) >>> 0;
    }
    return h >>> 0;
}

/**
 * mulberry32 PRNG: returns a function giving uniform numbers in [0, 1).
 * @param {number} seed uint32
 */
export function createRng(seed) {
    let a = seed >>> 0;
    return function rng() {
        a = (a + 0x6d2b79f5) >>> 0;
        let t = a;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Standard normal deviate (Box–Muller). */
export function normal(rng) {
    let u = 0;
    while (u <= 1e-12) u = rng();
    const v = rng();
    return Math.sqrt(-2 * Math.log(u)) * Math.cos(2 * Math.PI * v);
}

/**
 * Standard normal deviate truncated to [−limit, +limit] (rejection sampling; with
 * limit = 2.5 about 1.2 % of draws are rejected). Never exceeds the band.
 */
export function truncatedNormal(rng, limit = 2.5) {
    for (let i = 0; i < 64; i++) {
        const z = normal(rng);
        if (Math.abs(z) <= limit) return z;
    }
    return 0;
}

/**
 * Standard deviation of one reading for an instrument spec at `value`:
 *   σ = √(sigmaAbs² + (sigmaRel·|value|)²)
 */
export function readingSigma(value, spec = {}) {
    const a = spec.sigmaAbs ?? 0;
    const r = (spec.sigmaRel ?? 0) * Math.abs(value);
    return Math.sqrt(a * a + r * r);
}

/**
 * Worst-case |reading − true value| for a spec: limit·σ + resolution/2.
 * Used by the tests to prove that every task stays checkable.
 */
export function maxReadingError(value, spec = {}) {
    const limit = spec.limit ?? 2.5;
    return limit * readingSigma(value, spec) + (spec.resolution > 0 ? spec.resolution / 2 : 0);
}

/**
 * One instrument reading of `trueValue`.
 * @param {number} trueValue exact (model) value
 * @param {{resolution?:number, sigmaAbs?:number, sigmaRel?:number, limit?:number}} spec
 * @param {() => number} [rng] PRNG from createRng (omit → no scatter, only rounding)
 * @returns {number} quantized reading
 */
export function reading(trueValue, spec = {}, rng = null) {
    if (!Number.isFinite(trueValue)) return trueValue;
    const sigma = readingSigma(trueValue, spec);
    const noise = rng && sigma > 0 ? sigma * truncatedNormal(rng, spec.limit ?? 2.5) : 0;
    return quantize(trueValue + noise, spec.resolution ?? 0);
}

/**
 * A stateful instrument for one simulation: the k-th reading of the same set-up
 * (same `key`) is always the same number.
 *
 *   const timer = createInstrument(INSTRUMENTS.photogateTimer, 'pendulum');
 *   timer.read(2.0147, [L, theta0])   // → 2.015 (1st reading of this set-up)
 *
 * @param {object} spec instrument spec (see INSTRUMENTS)
 * @param {string} name simulation / instrument name used in the seed
 */
export function createInstrument(spec, name = '') {
    const counters = new Map();
    return {
        spec,
        /** @param {number} trueValue @param {Array} key set-up parameters (seed) */
        read(trueValue, key = []) {
            const k = seedFrom(name, ...key);
            const n = counters.get(k) ?? 0;
            counters.set(k, n + 1);
            return reading(trueValue, spec, createRng(seedFrom(k, n)));
        },
        /** Forget the reading counters (e.g. on "Qayta"). */
        reset() {
            counters.clear();
        },
        decimals: decimalsOf(spec.resolution ?? 0),
        format(value) {
            return formatReading(value, spec.resolution ?? 0);
        },
    };
}

/**
 * Instrument catalog. resolution = scale division / last digit; sigmaAbs, sigmaRel =
 * random scatter of one reading (1σ); accuracy = manufacturer accuracy class for the
 * documentation. Typical school / first-year university lab equipment.
 */
export const INSTRUMENTS = Object.freeze({
    /** Electronic stopwatch / photogate timer (lab timer, 1 ms display). */
    photogateTimer: Object.freeze({ resolution: 0.001, sigmaAbs: 0.0005, unit: 's', accuracy: '±1 ms' }),
    /** Hand stopwatch, 0.01 s display (human reaction scatter is in sigmaAbs). */
    handStopwatch: Object.freeze({ resolution: 0.01, sigmaAbs: 0.03, unit: 's', accuracy: '±0.01 s + reaction ≈ 0.1 s' }),
    /** Metre rule, 1 mm divisions (value in metres). */
    metreRule: Object.freeze({ resolution: 0.001, sigmaAbs: 0.0003, unit: 'm', accuracy: '±0.5 mm' }),
    /** Tape measure for long distances, 1 cm divisions (value in metres). */
    tapeMeasure: Object.freeze({ resolution: 0.01, sigmaAbs: 0.003, unit: 'm', accuracy: '±0.5 cm' }),
    /** Optical bench scale, 1 mm divisions (value in cm). */
    opticalBench: Object.freeze({ resolution: 0.1, sigmaAbs: 0.03, unit: 'cm', accuracy: '±0.5 mm' }),
    /** Measuring microscope / screen micrometer, 0.01 mm (value in mm). */
    screenMicrometer: Object.freeze({ resolution: 0.01, sigmaAbs: 0.003, unit: 'mm', accuracy: '±0.01 mm' }),
    /** Dial indicator (expansion apparatus), 0.01 mm (value in mm). */
    dialIndicator: Object.freeze({ resolution: 0.01, sigmaAbs: 0.002, unit: 'mm', accuracy: '±0.01 mm' }),
    /** School protractor / goniometer disc, 0.5° divisions (value in degrees). */
    protractor: Object.freeze({ resolution: 0.5, sigmaAbs: 0.1, unit: '°', accuracy: '±0.5°' }),
    /** Spectrometer / goniometer with vernier, 1′ ≈ 0.0167° (value in degrees). */
    goniometer: Object.freeze({ resolution: 0.01, sigmaAbs: 0.003, unit: '°', accuracy: "±1'" }),
    /** Digital thermometer, 0.1 °C. */
    digitalThermometer: Object.freeze({ resolution: 0.1, sigmaAbs: 0.03, unit: '°C', accuracy: '±0.2 °C' }),
    /** Digital multimeter, 3½ digits (relative scatter ≈ 1 count of a 2000-count range). */
    multimeter: Object.freeze({ resolution: 0, sigmaRel: 0.0005, unit: '', accuracy: '±(0.5 % + 2 digits)' }),
    /** Analogue school meter, accuracy class 1.5. */
    analogMeter: Object.freeze({ resolution: 0, sigmaRel: 0.002, unit: '', accuracy: 'class 1.5' }),
    /** Laboratory balance, 0.1 g (value in kg). */
    balance: Object.freeze({ resolution: 0.0001, sigmaAbs: 0.00003, unit: 'kg', accuracy: '±0.1 g' }),
    /** Dynamometer (spring balance), 0.05 N divisions. */
    dynamometer: Object.freeze({ resolution: 0.05, sigmaAbs: 0.01, unit: 'N', accuracy: '±0.05 N' }),
    /** Teslameter (Hall probe), 0.01 mT. */
    teslameter: Object.freeze({ resolution: 0.01, sigmaRel: 0.001, unit: 'mT', accuracy: '±1 %' }),
    /** Frequency counter / generator readout, 0.1 Hz. */
    frequencyCounter: Object.freeze({ resolution: 0.1, sigmaAbs: 0, unit: 'Hz', accuracy: '±0.1 Hz' }),
    /** Geiger–Müller counter: counts are integers (Poisson scatter is physical, not instrumental). */
    geigerCounter: Object.freeze({ resolution: 1, sigmaAbs: 0, unit: 'imp', accuracy: 'Poisson √N' }),
});

/**
 * Digital multimeter display: picks the lowest range that holds |value| on a
 * `counts`-count display (3½ digits = 2000 counts) and rounds to its last digit.
 *   dmmResolution(0.0425, 2000, [0.2, 2, 20, 200]) → 0.0001
 * @param {number} value reading in base units (A, V, Ω …)
 * @param {number} [counts=2000]
 * @param {number[]} [ranges] full-scale values, ascending
 * @returns {number} resolution (last digit) of the chosen range
 */
export function dmmResolution(value, counts = 2000, ranges = [0.2, 2, 20, 200, 2000, 20000, 200000, 2e6]) {
    const v = Math.abs(value);
    const range = ranges.find((r) => v < r) ?? ranges[ranges.length - 1];
    return range / counts;
}

/**
 * Digital multimeter reading: relative scatter `sigmaRel` (default 0.05 %), then
 * rounded to the last digit of the auto-selected range.
 */
export function dmmReading(value, rng = null, { counts = 2000, ranges, sigmaRel = 0.0005, limit = 2.5 } = {}) {
    if (!Number.isFinite(value)) return value;
    const noisy = rng ? value * (1 + sigmaRel * truncatedNormal(rng, limit)) : value;
    return quantize(noisy, dmmResolution(noisy, counts, ranges));
}
