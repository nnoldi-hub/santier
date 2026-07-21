<template>
    <div>
        <canvas
            ref="canvasEl"
            class="w-full border border-gray-300 rounded-lg bg-white touch-none"
            style="height: 160px"
            @pointerdown="startStroke"
            @pointermove="drawStroke"
            @pointerup="endStroke"
            @pointerleave="endStroke"
        ></canvas>
        <div class="flex items-center justify-between mt-1">
            <p class="text-xs text-gray-400">Semneaza cu mouse-ul sau degetul.</p>
            <button type="button" @click="clear" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">
                Sterge semnatura
            </button>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const canvasEl = ref(null);
let context = null;
let drawing = false;
let hasStrokes = false;

function resizeCanvas() {
    const canvas = canvasEl.value;
    if (!canvas) return;

    const ratio = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;

    context = canvas.getContext('2d');
    context.scale(ratio, ratio);
    context.lineWidth = 2;
    context.lineCap = 'round';
    context.strokeStyle = '#1f2937';
}

function pointerPosition(event) {
    const rect = canvasEl.value.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}

function startStroke(event) {
    if (!context) return;
    drawing = true;
    const { x, y } = pointerPosition(event);
    context.beginPath();
    context.moveTo(x, y);
}

function drawStroke(event) {
    if (!drawing || !context) return;
    const { x, y } = pointerPosition(event);
    context.lineTo(x, y);
    context.stroke();
    hasStrokes = true;
}

function endStroke() {
    if (!drawing) return;
    drawing = false;
    if (hasStrokes) {
        emit('update:modelValue', canvasEl.value.toDataURL('image/png'));
    }
}

function clear() {
    if (!context || !canvasEl.value) return;
    context.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height);
    hasStrokes = false;
    emit('update:modelValue', '');
}

onMounted(() => {
    resizeCanvas();
});
</script>
