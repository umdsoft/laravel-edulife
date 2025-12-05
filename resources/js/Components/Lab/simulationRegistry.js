// Simulation Component Registry - 57 Components (Complete Physics Lab)
import { defineAsyncComponent } from 'vue';

// Mechanics (10)
export const PendulumSimulation = defineAsyncComponent(() => import('./Simulations/PendulumSimulation.vue'));
export const FreeFallSimulation = defineAsyncComponent(() => import('./Simulations/FreeFallSimulation.vue'));
export const NewtonSecondSimulation = defineAsyncComponent(() => import('./Simulations/NewtonSecondSimulation.vue'));
export const InclinedPlaneSimulation = defineAsyncComponent(() => import('./Simulations/InclinedPlaneSimulation.vue'));
export const SpringOscillationSimulation = defineAsyncComponent(() => import('./Simulations/SpringOscillationSimulation.vue'));
export const ProjectileSimulation = defineAsyncComponent(() => import('./Simulations/ProjectileSimulation.vue'));
export const MomentumSimulation = defineAsyncComponent(() => import('./Simulations/MomentumSimulation.vue'));
export const CircularMotionSimulation = defineAsyncComponent(() => import('./Simulations/CircularMotionSimulation.vue'));
export const TorqueSimulation = defineAsyncComponent(() => import('./Simulations/TorqueSimulation.vue'));
export const EnergyConservationSimulation = defineAsyncComponent(() => import('./Simulations/EnergyConservationSimulation.vue'));

// Thermodynamics (8)
export const ThermalExpansionSimulation = defineAsyncComponent(() => import('./Simulations/ThermalExpansionSimulation.vue'));
export const SpecificHeatSimulation = defineAsyncComponent(() => import('./Simulations/SpecificHeatSimulation.vue'));
export const IdealGasSimulation = defineAsyncComponent(() => import('./Simulations/IdealGasSimulation.vue'));
export const HeatTransferSimulation = defineAsyncComponent(() => import('./Simulations/HeatTransferSimulation.vue'));
export const PhaseChangeSimulation = defineAsyncComponent(() => import('./Simulations/PhaseChangeSimulation.vue'));
export const CalorimetrySimulation = defineAsyncComponent(() => import('./Simulations/CalorimetrySimulation.vue'));
export const CarnotCycleSimulation = defineAsyncComponent(() => import('./Simulations/CarnotCycleSimulation.vue'));
export const EntropySimulation = defineAsyncComponent(() => import('./Simulations/EntropySimulation.vue'));

// Electricity (10)
export const OhmLawSimulation = defineAsyncComponent(() => import('./Simulations/OhmLawSimulation.vue'));
export const SeriesCircuitSimulation = defineAsyncComponent(() => import('./Simulations/SeriesCircuitSimulation.vue'));
export const ParallelCircuitSimulation = defineAsyncComponent(() => import('./Simulations/ParallelCircuitSimulation.vue'));
export const CapacitorSimulation = defineAsyncComponent(() => import('./Simulations/CapacitorSimulation.vue'));
export const ElectrostaticsSimulation = defineAsyncComponent(() => import('./Simulations/ElectrostaticsSimulation.vue'));
export const ElectricFieldSimulation = defineAsyncComponent(() => import('./Simulations/ElectricFieldSimulation.vue'));
export const ElectricMotorSimulation = defineAsyncComponent(() => import('./Simulations/ElectricMotorSimulation.vue'));
export const KirchhoffSimulation = defineAsyncComponent(() => import('./Simulations/KirchhoffSimulation.vue'));
export const ElectricPowerSimulation = defineAsyncComponent(() => import('./Simulations/ElectricPowerSimulation.vue'));
export const RCCircuitSimulation = defineAsyncComponent(() => import('./Simulations/RCCircuitSimulation.vue'));
export const DiodesSimulation = defineAsyncComponent(() => import('./Simulations/DiodesSimulation.vue'));

// Optics (9)
export const RefractionSimulation = defineAsyncComponent(() => import('./Simulations/RefractionSimulation.vue'));
export const ConvexLensSimulation = defineAsyncComponent(() => import('./Simulations/ConvexLensSimulation.vue'));
export const ConcaveLensSimulation = defineAsyncComponent(() => import('./Simulations/ConcaveLensSimulation.vue'));
export const InterferenceSimulation = defineAsyncComponent(() => import('./Simulations/InterferenceSimulation.vue'));
export const ReflectionSimulation = defineAsyncComponent(() => import('./Simulations/ReflectionSimulation.vue'));
export const DispersionSimulation = defineAsyncComponent(() => import('./Simulations/DispersionSimulation.vue'));
export const DiffractionSimulation = defineAsyncComponent(() => import('./Simulations/DiffractionSimulation.vue'));
export const CurvedMirrorSimulation = defineAsyncComponent(() => import('./Simulations/CurvedMirrorSimulation.vue'));
export const PolarizationSimulation = defineAsyncComponent(() => import('./Simulations/PolarizationSimulation.vue'));

// Waves (8)
export const WaveBasicsSimulation = defineAsyncComponent(() => import('./Simulations/WaveBasicsSimulation.vue'));
export const DopplerSimulation = defineAsyncComponent(() => import('./Simulations/DopplerSimulation.vue'));
export const SoundWavesSimulation = defineAsyncComponent(() => import('./Simulations/SoundWavesSimulation.vue'));
export const StandingWavesSimulation = defineAsyncComponent(() => import('./Simulations/StandingWavesSimulation.vue'));
export const ResonanceSimulation = defineAsyncComponent(() => import('./Simulations/ResonanceSimulation.vue'));
export const BeatsSimulation = defineAsyncComponent(() => import('./Simulations/BeatsSimulation.vue'));
export const DoubleSlitSimulation = defineAsyncComponent(() => import('./Simulations/DoubleSlitSimulation.vue'));
export const WaveInterferenceSimulation = defineAsyncComponent(() => import('./Simulations/WaveInterferenceSimulation.vue'));
export const EMSpectrumSimulation = defineAsyncComponent(() => import('./Simulations/EMSpectrumSimulation.vue'));

// Magnetism (6)
export const MagneticFieldSimulation = defineAsyncComponent(() => import('./Simulations/MagneticFieldSimulation.vue'));
export const ElectromagnetSimulation = defineAsyncComponent(() => import('./Simulations/ElectromagnetSimulation.vue'));
export const EMInductionSimulation = defineAsyncComponent(() => import('./Simulations/EMInductionSimulation.vue'));
export const TransformerSimulation = defineAsyncComponent(() => import('./Simulations/TransformerSimulation.vue'));
export const LenzLawSimulation = defineAsyncComponent(() => import('./Simulations/LenzLawSimulation.vue'));
export const GeneratorSimulation = defineAsyncComponent(() => import('./Simulations/GeneratorSimulation.vue'));

// Atomic (6)
export const PhotoelectricSimulation = defineAsyncComponent(() => import('./Simulations/PhotoelectricSimulation.vue'));
export const BohrModelSimulation = defineAsyncComponent(() => import('./Simulations/BohrModelSimulation.vue'));
export const RadioactiveDecaySimulation = defineAsyncComponent(() => import('./Simulations/RadioactiveDecaySimulation.vue'));
export const EmissionSpectraSimulation = defineAsyncComponent(() => import('./Simulations/EmissionSpectraSimulation.vue'));
export const NuclearFissionSimulation = defineAsyncComponent(() => import('./Simulations/NuclearFissionSimulation.vue'));
export const XRayDiffractionSimulation = defineAsyncComponent(() => import('./Simulations/XRayDiffractionSimulation.vue'));

// Component map for dynamic loading
export const simulationComponents = {
    // Mechanics
    'pendulum_simple': PendulumSimulation,
    'free_fall': FreeFallSimulation,
    'newton_second': NewtonSecondSimulation,
    'inclined_plane': InclinedPlaneSimulation,
    'spring_oscillation': SpringOscillationSimulation,
    'projectile': ProjectileSimulation,
    'momentum': MomentumSimulation,
    'circular_motion': CircularMotionSimulation,
    'torque': TorqueSimulation,
    'energy_conservation': EnergyConservationSimulation,
    
    // Thermodynamics
    'thermal_expansion': ThermalExpansionSimulation,
    'specific_heat': SpecificHeatSimulation,
    'ideal_gas': IdealGasSimulation,
    'heat_transfer': HeatTransferSimulation,
    'phase_change': PhaseChangeSimulation,
    'calorimetry': CalorimetrySimulation,
    'carnot_cycle': CarnotCycleSimulation,
    'entropy': EntropySimulation,
    
    // Electricity
    'ohm_law': OhmLawSimulation,
    'series_circuit': SeriesCircuitSimulation,
    'parallel_circuit': ParallelCircuitSimulation,
    'capacitor': CapacitorSimulation,
    'electrostatics': ElectrostaticsSimulation,
    'electric_field': ElectricFieldSimulation,
    'electric_motor': ElectricMotorSimulation,
    'kirchhoff': KirchhoffSimulation,
    'electric_power': ElectricPowerSimulation,
    'rc_circuit': RCCircuitSimulation,
    'diodes': DiodesSimulation,
    
    // Optics
    'refraction': RefractionSimulation,
    'convex_lens': ConvexLensSimulation,
    'concave_lens': ConcaveLensSimulation,
    'interference': InterferenceSimulation,
    'reflection': ReflectionSimulation,
    'dispersion': DispersionSimulation,
    'diffraction': DiffractionSimulation,
    'curved_mirrors': CurvedMirrorSimulation, // Fixed key to match DB
    'polarization': PolarizationSimulation,
    
    // Waves
    'wave_basics': WaveBasicsSimulation,
    'doppler': DopplerSimulation,
    'sound_waves': SoundWavesSimulation,
    'standing_waves': StandingWavesSimulation,
    'resonance': ResonanceSimulation,
    'beats': BeatsSimulation,
    'double_slit': DoubleSlitSimulation,
    'wave_interference': WaveInterferenceSimulation, // Added
    'em_spectrum': EMSpectrumSimulation,
    
    // Magnetism
    'magnetic_field': MagneticFieldSimulation,
    'electromagnet': ElectromagnetSimulation,
    'em_induction': EMInductionSimulation,
    'transformer': TransformerSimulation,
    'lenz_law': LenzLawSimulation,
    'generator': GeneratorSimulation,
    
    // Atomic
    'photoelectric': PhotoelectricSimulation,
    'bohr_model': BohrModelSimulation,
    'radioactive_decay': RadioactiveDecaySimulation,
    'emission_spectra': EmissionSpectraSimulation,
    'nuclear_fission': NuclearFissionSimulation,
    'xray_diffraction': XRayDiffractionSimulation,
};

// Get simulation component by type
export function getSimulationComponent(simulationType) {
    return simulationComponents[simulationType] || null;
}

// Check if simulation is available
export function isSimulationAvailable(simulationType) {
    return simulationType in simulationComponents;
}

// Get all available simulation types
export function getAllSimulationTypes() {
    return Object.keys(simulationComponents);
}

// Get count by category
export function getSimulationStats() {
    return {
        mechanics: 10,
        thermodynamics: 8,
        electricity: 10,
        optics: 9,
        waves: 8,
        magnetism: 6,
        atomic: 6,
        total: 57
    };
}
