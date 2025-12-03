<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Hls from 'hls.js';

const props = defineProps({
    src: String,
    poster: String,
    startTime: {
        type: Number,
        default: 0
    },
    chapters: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['progress', 'ended', 'timeupdate', 'loadedmetadata']);

const videoRef = ref(null);
const containerRef = ref(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const playbackRate = ref(1);
const isFullscreen = ref(false);
const showControls = ref(true);
const qualityLevels = ref([]);
const currentQuality = ref(-1); // -1 = auto
const isLoading = ref(true);

let hls = null;
let controlsTimeout = null;
let progressInterval = null;

onMounted(() => {
    initPlayer();
});

onBeforeUnmount(() => {
    if (hls) {
        hls.destroy();
    }
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});

const initPlayer = () => {
    const video = videoRef.value;

    if (Hls.isSupported()) {
        hls = new Hls();
        hls.loadSource(props.src);
        hls.attachMedia(video);

        hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
            qualityLevels.value = data.levels.map((level, index) => ({
                index,
                height: level.height,
                bitrate: level.bitrate
            }));
            isLoading.value = false;
            if (props.startTime > 0) {
                video.currentTime = props.startTime;
            }
        });

        hls.on(Hls.Events.LEVEL_SWITCHED, (event, data) => {
            // Quality switched
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = props.src;
        video.addEventListener('loadedmetadata', () => {
            isLoading.value = false;
            if (props.startTime > 0) {
                video.currentTime = props.startTime;
            }
        });
    }

    // Progress tracking
    progressInterval = setInterval(() => {
        if (isPlaying.value) {
            emit('progress', {
                currentTime: video.currentTime,
                duration: video.duration
            });
        }
    }, 10000); // Every 10 seconds
};

const togglePlay = () => {
    const video = videoRef.value;
    if (video.paused) {
        video.play();
        isPlaying.value = true;
    } else {
        video.pause();
        isPlaying.value = false;
    }
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
    emit('ended');
};

const seek = (event) => {
    const video = videoRef.value;
    const rect = event.target.getBoundingClientRect();
    const pos = (event.clientX - rect.left) / rect.width;
    video.currentTime = pos * video.duration;
};

const setVolume = (val) => {
    const video = videoRef.value;
    volume.value = val;
    video.volume = val;
};

const setPlaybackRate = (rate) => {
    const video = videoRef.value;
    playbackRate.value = rate;
    video.playbackRate = rate;
};

const setQuality = (index) => {
    currentQuality.value = index;
    if (hls) {
        hls.currentLevel = index;
    }
};

const toggleFullscreen = () => {
    const container = containerRef.value;
    if (!document.fullscreenElement) {
        container.requestFullscreen();
        isFullscreen.value = true;
    } else {
        document.exitFullscreen();
        isFullscreen.value = false;
    }
};

const formatTime = (seconds) => {
    if (!seconds) return '00:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

const handleMouseMove = () => {
    showControls.value = true;
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(() => {
        if (isPlaying.value) {
            showControls.value = false;
        }
    }, 3000);
};
</script>

<template>
    <div ref="containerRef" class="relative bg-black group aspect-video rounded-xl overflow-hidden"
        @mousemove="handleMouseMove" @mouseleave="showControls = false">

        <video ref="videoRef" class="w-full h-full object-contain" :poster="poster" @click="togglePlay"
            @timeupdate="onTimeUpdate" @loadedmetadata="onLoadedMetadata" @ended="onEnded" @waiting="isLoading = true"
            @playing="isLoading = false">
        </video>

        <!-- Loading Spinner -->
        <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-black/50 z-10">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
        </div>

        <!-- Big Play Button -->
        <div v-if="!isPlaying && !isLoading"
            class="absolute inset-0 flex items-center justify-center bg-black/30 cursor-pointer z-10"
            @click="togglePlay">
            <button
                class="w-20 h-20 bg-purple-600/90 rounded-full flex items-center justify-center hover:bg-purple-600 transition-colors shadow-lg transform hover:scale-105">
                <svg class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </button>
        </div>

        <!-- Controls -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent px-4 py-4 transition-opacity duration-300 z-20"
            :class="{ 'opacity-0': !showControls && isPlaying, 'opacity-100': showControls || !isPlaying }">

            <!-- Progress Bar -->
            <div class="relative h-1.5 bg-white/30 rounded-full cursor-pointer mb-4 group/progress" @click="seek">
                <div class="absolute top-0 left-0 h-full bg-purple-500 rounded-full"
                    :style="{ width: `${(currentTime / duration) * 100}%` }">
                    <div
                        class="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full shadow scale-0 group-hover/progress:scale-100 transition-transform">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Play/Pause -->
                    <button @click="togglePlay" class="text-white hover:text-purple-400 transition-colors">
                        <svg v-if="isPlaying" class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                        </svg>
                        <svg v-else class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </button>

                    <!-- Volume -->
                    <div class="flex items-center gap-2 group/volume">
                        <button class="text-white hover:text-purple-400">
                            <svg v-if="volume > 0.5" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                            </svg>
                            <svg v-else-if="volume > 0" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z" />
                            </svg>
                        </button>
                        <input type="range" min="0" max="1" step="0.1" v-model="volume"
                            @input="setVolume($event.target.value)"
                            class="w-0 group-hover/volume:w-20 transition-all h-1 bg-white/30 rounded-lg appearance-none cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-3 [&::-webkit-slider-thumb]:h-3 [&::-webkit-slider-thumb]:bg-white [&::-webkit-slider-thumb]:rounded-full">
                    </div>

                    <!-- Time -->
                    <div class="text-white text-sm font-medium">
                        {{ formatTime(currentTime) }} / {{ formatTime(duration) }}
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Playback Rate -->
                    <div class="relative group/rate">
                        <button class="text-white hover:text-purple-400 font-bold text-sm w-8">
                            {{ playbackRate }}x
                        </button>
                        <div
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-black/90 rounded-lg p-2 hidden group-hover/rate:block min-w-[60px]">
                            <button v-for="rate in [0.5, 0.75, 1, 1.25, 1.5, 2]" :key="rate"
                                @click="setPlaybackRate(rate)"
                                class="block w-full text-left px-2 py-1 text-sm hover:bg-white/20 rounded"
                                :class="{ 'text-purple-400': playbackRate === rate, 'text-white': playbackRate !== rate }">
                                {{ rate }}x
                            </button>
                        </div>
                    </div>

                    <!-- Quality -->
                    <div v-if="qualityLevels.length > 0" class="relative group/quality">
                        <button class="text-white hover:text-purple-400">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L5.09 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.58 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
                            </svg>
                        </button>
                        <div
                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-black/90 rounded-lg p-2 hidden group-hover/quality:block min-w-[80px]">
                            <button @click="setQuality(-1)"
                                class="block w-full text-left px-2 py-1 text-sm hover:bg-white/20 rounded"
                                :class="{ 'text-purple-400': currentQuality === -1, 'text-white': currentQuality !== -1 }">
                                Auto
                            </button>
                            <button v-for="level in qualityLevels" :key="level.index" @click="setQuality(level.index)"
                                class="block w-full text-left px-2 py-1 text-sm hover:bg-white/20 rounded"
                                :class="{ 'text-purple-400': currentQuality === level.index, 'text-white': currentQuality !== level.index }">
                                {{ level.height }}p
                            </button>
                        </div>
                    </div>

                    <!-- Fullscreen -->
                    <button @click="toggleFullscreen" class="text-white hover:text-purple-400">
                        <svg v-if="isFullscreen" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
