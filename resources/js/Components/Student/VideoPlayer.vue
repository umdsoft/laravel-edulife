<script setup>
/**
 * VideoPlayer — HLS lesson player.
 * API unchanged: props src, poster, startTime, chapters; emits progress, ended, timeupdate, loadedmetadata.
 * New: exposes seek(seconds) / play() / pause() (Lesson page uses seek for notes & chapters);
 * keyboard shortcuts when focused (Space/K play, ←/→ 5s, J/L 10s, M mute, F fullscreen);
 * click-to-open menus (touch friendly) and an accessible range seek bar.
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import {
    PlayIcon, PauseIcon, SpeakerWaveIcon, SpeakerXMarkIcon, ArrowsPointingOutIcon, ArrowsPointingInIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/solid';

const props = defineProps({
    src: String,
    poster: String,
    startTime: {
        type: Number,
        default: 0,
    },
    chapters: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['progress', 'ended', 'timeupdate', 'loadedmetadata']);

const videoRef = ref(null);
const containerRef = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const muted = ref(false);
const playbackRate = ref(1);
const isFullscreen = ref(false);
const showControls = ref(true);
const qualityLevels = ref([]);
const currentQuality = ref(-1); // -1 = auto
const isLoading = ref(true);
const openMenu = ref(null); // 'rate' | 'quality' | null

const rates = [0.5, 0.75, 1, 1.25, 1.5, 2];

let hls = null;
let isUnmounted = false;
let controlsTimeout = null;
let progressInterval = null;

const onFullscreenChange = () => {
    isFullscreen.value = document.fullscreenElement === containerRef.value;
};

onMounted(() => {
    initPlayer();
    document.addEventListener('fullscreenchange', onFullscreenChange);
});

onBeforeUnmount(() => {
    isUnmounted = true;
    if (hls) {
        hls.destroy();
    }
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    clearTimeout(controlsTimeout);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
});

const initPlayer = async () => {
    const video = videoRef.value;

    // hls.js is large (~500 kB), so load it only when a video player is actually rendered
    const { default: Hls } = await import('hls.js');
    if (isUnmounted) return;

    if (Hls.isSupported()) {
        hls = new Hls();
        hls.loadSource(props.src);
        hls.attachMedia(video);

        hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            qualityLevels.value = data.levels.map((level, index) => ({
                index,
                height: level.height,
                bitrate: level.bitrate,
            }));
            isLoading.value = false;
            if (props.startTime > 0) {
                video.currentTime = props.startTime;
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = props.src;
        video.addEventListener('loadedmetadata', () => {
            isLoading.value = false;
            if (props.startTime > 0) {
                video.currentTime = props.startTime;
            }
        });
    } else {
        isLoading.value = false;
    }

    // Progress tracking
    progressInterval = setInterval(() => {
        if (isPlaying.value) {
            emit('progress', {
                currentTime: video.currentTime,
                duration: video.duration,
            });
        }
    }, 10000); // Every 10 seconds
};

const play = () => {
    const video = videoRef.value;
    if (!video) return;
    const p = video.play();
    if (p && typeof p.catch === 'function') p.catch(() => {});
    isPlaying.value = true;
};

const pause = () => {
    const video = videoRef.value;
    if (!video) return;
    video.pause();
    isPlaying.value = false;
};

const togglePlay = () => {
    const video = videoRef.value;
    if (!video) return;
    if (video.paused) play();
    else pause();
};

const onTimeUpdate = () => {
    const video = videoRef.value;
    currentTime.value = video.currentTime;
    emit('timeupdate', video.currentTime);
};

const onLoadedMetadata = () => {
    const video = videoRef.value;
    duration.value = video.duration;
    emit('loadedmetadata', video.duration);
};

const onEnded = () => {
    isPlaying.value = false;
    showControls.value = true;
    emit('ended');
};

const seek = (seconds) => {
    const video = videoRef.value;
    if (!video || !Number.isFinite(Number(seconds))) return;
    const max = Number.isFinite(video.duration) ? video.duration : Number(seconds);
    video.currentTime = Math.max(0, Math.min(max, Number(seconds)));
    currentTime.value = video.currentTime;
};

const onSeekInput = (event) => seek(Number(event.target.value));
const skip = (delta) => seek((videoRef.value?.currentTime || 0) + delta);

const setVolume = (val) => {
    const video = videoRef.value;
    const v = Math.max(0, Math.min(1, Number(val)));
    volume.value = v;
    video.volume = v;
    muted.value = v === 0;
    video.muted = muted.value;
};

const toggleMute = () => {
    const video = videoRef.value;
    muted.value = !muted.value;
    video.muted = muted.value;
    if (!muted.value && volume.value === 0) setVolume(0.6);
};

const setPlaybackRate = (rate) => {
    const video = videoRef.value;
    playbackRate.value = rate;
    video.playbackRate = rate;
    openMenu.value = null;
};

const setQuality = (index) => {
    currentQuality.value = index;
    if (hls) {
        hls.currentLevel = index;
    }
    openMenu.value = null;
};

const toggleFullscreen = () => {
    const container = containerRef.value;
    if (!document.fullscreenElement) {
        container.requestFullscreen?.();
        isFullscreen.value = true;
    } else {
        document.exitFullscreen?.();
        isFullscreen.value = false;
    }
};

const toggleMenu = (name) => (openMenu.value = openMenu.value === name ? null : name);

const formatTime = (seconds) => {
    if (!seconds || !Number.isFinite(seconds)) return '00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    const mmss = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    return h > 0 ? `${h}:${mmss}` : mmss;
};

const progressPct = computed(() => (duration.value ? (currentTime.value / duration.value) * 100 : 0));
const chapterMarks = computed(() =>
    duration.value
        ? (props.chapters || [])
            .filter((c) => c.timestamp > 0 && c.timestamp < duration.value)
            .map((c) => ({ id: c.id, left: (c.timestamp / duration.value) * 100, title: c.title }))
        : [],
);

const revealControls = () => {
    showControls.value = true;
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(() => {
        if (isPlaying.value && !openMenu.value) {
            showControls.value = false;
        }
    }, 3000);
};

const onKeydown = (e) => {
    const tag = e.target?.tagName;
    if (tag === 'INPUT' && e.target.type === 'range' && ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) return;
    const key = e.key.toLowerCase();
    if (key === ' ' || key === 'k') {
        if (tag === 'BUTTON' && key === ' ') return; // let buttons handle Space themselves
        e.preventDefault();
        togglePlay();
    } else if (key === 'arrowleft') { e.preventDefault(); skip(-5); }
    else if (key === 'arrowright') { e.preventDefault(); skip(5); }
    else if (key === 'j') skip(-10);
    else if (key === 'l') skip(10);
    else if (key === 'm') toggleMute();
    else if (key === 'f') toggleFullscreen();
    else if (key === 'escape') openMenu.value = null;
    else return;
    revealControls();
};

defineExpose({ seek, play, pause });

const ctrlBtn = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 inline-flex h-10 w-10 items-center justify-center rounded-md text-white/90 transition-colors hover:bg-white/10 hover:text-white';
</script>

<template>
    <div ref="containerRef" tabindex="0" role="region" aria-label="Video pleer (Bo'shliq — ijro/pauza, ← → — 5 soniya)"
        class="group relative aspect-video overflow-hidden rounded-lg bg-black focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
        @mousemove="revealControls" @mouseleave="isPlaying && !openMenu && (showControls = false)" @keydown="onKeydown"
        @focusin="revealControls">

        <video ref="videoRef" class="h-full w-full object-contain" :poster="poster" playsinline @click="togglePlay"
            @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMetadata" @ended="onEnded" @waiting="isLoading = true"
            @playing="isLoading = false" @pause="isPlaying = false" @play="isPlaying = true">
        </video>

        <!-- Loading -->
        <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-black/40" aria-hidden="true">
            <div class="h-10 w-10 animate-spin rounded-full border-[3px] border-white/80 border-t-transparent motion-reduce:animate-none" />
        </div>

        <!-- Big play button -->
        <div v-if="!isPlaying && !isLoading" class="absolute inset-0 z-10 flex cursor-pointer items-center justify-center bg-black/25"
            @click="togglePlay">
            <button type="button" aria-label="Ijro etish" @click.stop="togglePlay"
                class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg transition-transform duration-150 hover:scale-105 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/60 motion-reduce:hover:scale-100">
                <PlayIcon class="ml-1 h-8 w-8" aria-hidden="true" />
            </button>
        </div>

        <!-- Controls -->
        <div class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-black/85 via-black/40 to-transparent px-3 pb-2 pt-10 transition-opacity duration-200 sm:px-4"
            :class="showControls || !isPlaying ? 'opacity-100' : 'pointer-events-none opacity-0'">

            <!-- Seek bar -->
            <div class="relative mb-1 flex h-5 items-center">
                <div class="pointer-events-none absolute inset-x-0 h-1 rounded-full bg-white/25">
                    <div class="h-full rounded-full bg-brand-500" :style="{ width: `${progressPct}%` }" />
                    <span v-for="m in chapterMarks" :key="m.id" class="absolute top-0 h-1 w-0.5 bg-black/60"
                        :style="{ left: `${m.left}%` }" />
                </div>
                <input type="range" min="0" :max="duration || 0" step="0.1" :value="currentTime" aria-label="Video vaqti"
                    :aria-valuetext="`${formatTime(currentTime)} / ${formatTime(duration)}`"
                    class="video-range relative h-5 w-full cursor-pointer appearance-none bg-transparent" @input="onSeekInput">
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <button type="button" :class="ctrlBtn" :aria-label="isPlaying ? 'Pauza' : 'Ijro etish'" @click="togglePlay">
                        <PauseIcon v-if="isPlaying" class="h-6 w-6" aria-hidden="true" />
                        <PlayIcon v-else class="h-6 w-6" aria-hidden="true" />
                    </button>

                    <div class="group/volume flex items-center">
                        <button type="button" :class="ctrlBtn" :aria-label="muted || volume === 0 ? 'Ovozni yoqish' : 'Ovozni o\'chirish'"
                            @click="toggleMute">
                            <SpeakerXMarkIcon v-if="muted || volume === 0" class="h-5 w-5" aria-hidden="true" />
                            <SpeakerWaveIcon v-else class="h-5 w-5" aria-hidden="true" />
                        </button>
                        <input type="range" min="0" max="1" step="0.05" :value="muted ? 0 : volume" aria-label="Ovoz balandligi"
                            class="video-range video-volume hidden h-5 w-0 cursor-pointer appearance-none bg-transparent transition-[width] duration-150 focus:w-20 group-hover/volume:w-20 sm:block"
                            @input="setVolume($event.target.value)">
                    </div>

                    <span class="ml-1 text-xs font-medium text-white/90 tabular-nums sm:text-13">
                        {{ formatTime(currentTime) }} <span class="text-white/50">/ {{ formatTime(duration) }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-1">
                    <!-- Playback rate -->
                    <div class="relative">
                        <button type="button" :class="[ctrlBtn, 'w-auto px-2 text-13 font-semibold tabular-nums']"
                            :aria-expanded="openMenu === 'rate'" aria-haspopup="true" aria-label="Ijro tezligi"
                            @click="toggleMenu('rate')">
                            {{ playbackRate }}x
                        </button>
                        <div v-if="openMenu === 'rate'" role="menu" aria-label="Ijro tezligi"
                            class="absolute bottom-full right-0 mb-2 min-w-[88px] rounded-md bg-black/90 p-1 shadow-lg ring-1 ring-white/10">
                            <button v-for="rate in rates" :key="rate" type="button" role="menuitemradio"
                                :aria-checked="playbackRate === rate" @click="setPlaybackRate(rate)"
                                class="block w-full rounded-sm px-2.5 py-1.5 text-left text-13 tabular-nums hover:bg-white/15 focus-visible:bg-white/15 focus-visible:outline-none"
                                :class="playbackRate === rate ? 'bg-white/15 font-semibold text-white' : 'text-white/85'">
                                {{ rate }}x
                            </button>
                        </div>
                    </div>

                    <!-- Quality -->
                    <div v-if="qualityLevels.length > 0" class="relative">
                        <button type="button" :class="ctrlBtn" :aria-expanded="openMenu === 'quality'" aria-haspopup="true"
                            aria-label="Video sifati" @click="toggleMenu('quality')">
                            <Cog6ToothIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                        <div v-if="openMenu === 'quality'" role="menu" aria-label="Video sifati"
                            class="absolute bottom-full right-0 mb-2 min-w-[96px] rounded-md bg-black/90 p-1 shadow-lg ring-1 ring-white/10">
                            <button type="button" role="menuitemradio" :aria-checked="currentQuality === -1" @click="setQuality(-1)"
                                class="block w-full rounded-sm px-2.5 py-1.5 text-left text-13 hover:bg-white/15 focus-visible:bg-white/15 focus-visible:outline-none"
                                :class="currentQuality === -1 ? 'bg-white/15 font-semibold text-white' : 'text-white/85'">
                                Avto
                            </button>
                            <button v-for="level in qualityLevels" :key="level.index" type="button" role="menuitemradio"
                                :aria-checked="currentQuality === level.index" @click="setQuality(level.index)"
                                class="block w-full rounded-sm px-2.5 py-1.5 text-left text-13 tabular-nums hover:bg-white/15 focus-visible:bg-white/15 focus-visible:outline-none"
                                :class="currentQuality === level.index ? 'bg-white/15 font-semibold text-white' : 'text-white/85'">
                                {{ level.height }}p
                            </button>
                        </div>
                    </div>

                    <button type="button" :class="ctrlBtn" :aria-label="isFullscreen ? 'To\'liq ekrandan chiqish' : 'To\'liq ekran'"
                        @click="toggleFullscreen">
                        <ArrowsPointingInIcon v-if="isFullscreen" class="h-5 w-5" aria-hidden="true" />
                        <ArrowsPointingOutIcon v-else class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Thin, accessible range inputs on a dark video surface */
.video-range::-webkit-slider-runnable-track {
    height: 4px;
    background: transparent;
}
.video-range::-moz-range-track {
    height: 4px;
    background: transparent;
}
.video-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 14px;
    height: 14px;
    margin-top: -5px;
    border-radius: 9999px;
    background: #fff;
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.4);
}
.video-range::-moz-range-thumb {
    width: 14px;
    height: 14px;
    border: 0;
    border-radius: 9999px;
    background: #fff;
}
.video-volume::-webkit-slider-runnable-track {
    background: rgb(255 255 255 / 0.3);
    border-radius: 9999px;
}
.video-volume::-moz-range-track {
    background: rgb(255 255 255 / 0.3);
    border-radius: 9999px;
}
.video-range:focus-visible {
    outline: none;
}
.video-range:focus-visible::-webkit-slider-thumb {
    box-shadow: 0 0 0 3px rgb(255 255 255 / 0.5);
}
</style>
