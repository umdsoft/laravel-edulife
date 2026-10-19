import { describe, it, expect } from 'vitest';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import {
    labUrls,
    taskAnswerKind,
    normalizeTasksProgress,
    completedCount,
    progressPercent,
    nextOpenStep,
    buildCheckPayload,
    MAX_SUBMITTED,
} from '../labTasks.js';

const TASKS = [
    { step: 1, type: 'instruction' },
    { step: 2, type: 'measurement' },
    { step: 3, type: 'calculation' },
    { step: 4, type: 'conclusion' },
];

describe('labUrls', () => {
    it('builds the lab API paths (not the global route() helper, which does not know them)', () => {
        expect(labUrls.checkTask('a-1')).toBe('/student/lab/api/attempt/a-1/check-task');
        expect(labUrls.measurement('a-1')).toBe('/student/lab/api/attempt/a-1/measurement');
        expect(labUrls.complete('a-1')).toBe('/student/lab/api/attempt/a-1/complete');
        expect(labUrls.experiment('simple-pendulum')).toBe('/student/lab/experiment/simple-pendulum');
    });
});

describe('task progress', () => {
    it('normalises the server map and the raw stored array', () => {
        expect(normalizeTasksProgress(null)).toEqual({});
        expect(normalizeTasksProgress([])).toEqual({});
        const fromArray = normalizeTasksProgress([{ step: 2, status: 'completed', score: 10 }, { step: 3, status: 'in_progress' }]);
        expect(fromArray[2].completed).toBe(true);
        expect(fromArray[3].completed).toBe(false);
        const fromMap = normalizeTasksProgress({ 2: { step: 2, completed: true, score: 10 } });
        expect(fromMap[2]).toMatchObject({ completed: true, score: 10 });
    });

    it('counts completion and finds the next open task', () => {
        const progress = { 1: { completed: true }, 2: { completed: false }, 4: { completed: true } };
        expect(completedCount(progress, TASKS)).toBe(2);
        expect(progressPercent(progress, TASKS)).toBe(50);
        expect(progressPercent({}, [])).toBe(0);
        expect(nextOpenStep(TASKS, progress, 1)).toBe(2);
        expect(nextOpenStep(TASKS, progress, 3)).toBe(2); // wraps around
        const all = Object.fromEntries(TASKS.map((t) => [t.step, { completed: true }]));
        expect(progressPercent(all, TASKS)).toBe(100);
        expect(nextOpenStep(TASKS, all, 4)).toBe(4);
    });
});

describe('buildCheckPayload', () => {
    it('never sends a score', () => {
        for (const task of TASKS) {
            const payload = buildCheckPayload(task, {});
            expect(payload.task_number).toBe(task.step);
            expect(payload).not.toHaveProperty('score');
            expect(payload).not.toHaveProperty('max_score');
        }
        expect(taskAnswerKind(TASKS[0])).toBe('ack');
        expect(taskAnswerKind({ type: 'graph_analysis' })).toBe('text');
    });

    it('sends the numeric measurements recorded during the task', () => {
        const measurements = [
            { name: 'Tebranish davri (T)', value: '1.42', unit: 's', step: 3 },
            { name: 'Tebranish davri (T)', value: '2.01', unit: 's', step: 2 },
            { name: 'Kuch', value: '—', unit: 'N', step: 2 },
        ];
        expect(buildCheckPayload(TASKS[1], { measurements })).toEqual({
            task_number: 2,
            measurements: [{ name: 'Tebranish davri (T)', value: 2.01, unit: 's' }],
        });

        // nothing recorded during this step → the most recent ones overall
        const other = buildCheckPayload({ step: 5, type: 'measurement' }, { measurements });
        expect(other.measurements.map((m) => m.value)).toEqual([1.42, 2.01]);

        const many = Array.from({ length: 30 }, (_, i) => ({ name: 'T', value: i, unit: 's', step: 2 }));
        const capped = buildCheckPayload(TASKS[1], { measurements: many }).measurements;
        expect(capped).toHaveLength(MAX_SUBMITTED);
        expect(capped.at(-1).value).toBe(29);
    });

    it('sends calculator results for calculation tasks and text for written tasks', () => {
        const calculations = [{ formula_id: 'gravity', inputs: { L: 1, T: 2 }, result: 9.8696, unit: 'm/s²', step: 3 }];
        expect(buildCheckPayload(TASKS[2], { calculations })).toEqual({
            task_number: 3,
            calculations: [{ formula_id: 'gravity', result: 9.8696, unit: 'm/s²' }],
        });
        expect(buildCheckPayload(TASKS[3], { text: 'Xulosa' })).toEqual({ task_number: 4, text: 'Xulosa' });
        expect(buildCheckPayload(TASKS[0], { text: 'x' })).toEqual({ task_number: 1 });
    });
});

// The server matches a measurement by name prefix (expected.measurement). Make sure every
// seeded prefix is actually emitted by the experiment's simulation, otherwise the task
// could never be completed.
describe('seeded measurement names are emitted by the simulations', () => {
    const LAB_DATA = new URL('../../../../../database/seeders/data/lab/experiments/', import.meta.url);
    const SIMS = new URL('../Simulations/', import.meta.url);
    const registry = readFileSync(fileURLToPath(new URL('../simulationRegistry.js', import.meta.url)), 'utf8');
    const componentOf = Object.fromEntries(
        [...registry.matchAll(/^\s*'([a-z_]+)':\s*([A-Za-z]+Simulation),/gm)].map((m) => [m[1], m[2]])
    );
    const experiments = readdirSync(fileURLToPath(LAB_DATA)).flatMap((f) =>
        JSON.parse(readFileSync(fileURLToPath(new URL(f, LAB_DATA)), 'utf8'))
    );
    const cases = experiments.flatMap((e) =>
        e.tasks
            .filter((t) => t.expected?.source === 'measurement')
            .map((t) => [`${e.slug}: ${t.expected.measurement}`, e.simulation_type, t.expected.measurement])
    );

    it('has measurement tasks to check', () => {
        expect(cases.length).toBe(128);
    });

    it.each(cases)('%s', (_label, simType, prefix) => {
        const source = readFileSync(fileURLToPath(new URL(`${componentOf[simType]}.vue`, SIMS)), 'utf8');
        const emits = [...source.matchAll(/emit\('measurement',\s*\{\s*name:\s*(['"`])((?:\\.|(?!\1).)*)\1/gs)]
            .map((m) => m[2].replace(/\\(.)/g, '$1'));
        expect(emits.length, `${simType} emits measurements`).toBeGreaterThan(0);
        expect(emits.some((name) => name.startsWith(prefix)), `${simType} emits a name starting with "${prefix}"`).toBe(true);
    });
});
