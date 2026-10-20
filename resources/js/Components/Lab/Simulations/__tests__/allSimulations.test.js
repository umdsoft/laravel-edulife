import { describe, it, expect } from 'vitest';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';

// Every seeded experiment's simulation mounts with its seeded config, renders,
// runs 3 s of animation and unmounts without a Vue warning or a runtime error.
const LAB = new URL('../../../../../../database/seeders/data/lab/experiments/', import.meta.url);
const registry = readFileSync(fileURLToPath(new URL('../../simulationRegistry.js', import.meta.url)), 'utf8');
const SIM = Object.fromEntries([...registry.matchAll(/^\s*'([a-z_]+)':\s*([A-Za-z]+Simulation),/gm)].map((m) => [m[1], m[2]]));
const EXPERIMENTS = readdirSync(fileURLToPath(LAB))
    .filter((f) => f.endsWith('.json'))
    .flatMap((f) => JSON.parse(readFileSync(fileURLToPath(new URL(f, LAB)), 'utf8')));

const START = ['start', 'startSimulation', 'startProcess', 'startHeating', 'startFission', 'mix'];

describe('every simulation mounts and runs headless', () => {
    it.each(EXPERIMENTS.map((e) => [e.slug, e]))('%s', async (_slug, exp) => {
        const sim = await mountSimulation(SIM[exp.simulation_type], { config: exp.simulation_config, currentTask: exp.tasks[0] });
        try {
            const start = START.find((f) => typeof sim.setup[f] === 'function');
            if (start) sim.call(start);
            await sim.advance(3);
            expect(sim.errors.map((e) => e.message)).toEqual([]);
            expect(sim.warnings).toEqual([]);
            expect(sim.text().length).toBeGreaterThan(0);
            expect(sim.text()).not.toMatch(/\bNaN\b|undefined|Infinity/);
        } finally {
            sim.unmount();
        }
    });
});
