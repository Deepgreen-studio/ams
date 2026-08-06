<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center" @click.self="close">
    <div class="w-full max-w-3xl overflow-hidden rounded-xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Crop image</h3>
          <p class="text-xs text-slate-500">Drag the selection, then apply crop before upload.</p>
        </div>
        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100" @click="close">Close</button>
      </div>
      <div class="space-y-4 p-5">
        <label class="inline-flex cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Choose image
          <input type="file" accept="image/*" class="hidden" @change="onFile" />
        </label>
        <div v-if="previewUrl" class="overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div class="relative inline-block max-w-full">
            <img ref="imageEl" :src="previewUrl" alt="Crop source" class="max-h-[50vh] max-w-full select-none" @load="onImageLoad" />
            <div
              v-if="ready"
              class="absolute border-2 border-brand-500 bg-brand-500/10"
              :style="cropStyle"
              @pointerdown="startDrag"
            />
          </div>
        </div>
        <div v-if="ready" class="grid gap-3 sm:grid-cols-4">
          <label class="text-xs text-slate-600">X<input v-model.number="crop.x" type="number" min="0" class="mt-1 h-12 w-full rounded-[12px] border border-slate-300 px-2 text-sm" /></label>
          <label class="text-xs text-slate-600">Y<input v-model.number="crop.y" type="number" min="0" class="mt-1 h-12 w-full rounded-[12px] border border-slate-300 px-2 text-sm" /></label>
          <label class="text-xs text-slate-600">Width<input v-model.number="crop.width" type="number" min="1" class="mt-1 h-12 w-full rounded-[12px] border border-slate-300 px-2 text-sm" /></label>
          <label class="text-xs text-slate-600">Height<input v-model.number="crop.height" type="number" min="1" class="mt-1 h-12 w-full rounded-[12px] border border-slate-300 px-2 text-sm" /></label>
        </div>
      </div>
      <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
        <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50" @click="close">Cancel</button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="!ready || processing"
          @click="applyCrop"
        >
          {{ processing ? 'Processing…' : 'Apply & upload' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'cropped']);

const imageEl = ref(null);
const previewUrl = ref('');
const sourceFile = ref(null);
const ready = ref(false);
const processing = ref(false);
const natural = reactive({ width: 0, height: 0 });
const display = reactive({ width: 0, height: 0 });
const crop = reactive({ x: 0, y: 0, width: 200, height: 200 });
const drag = reactive({ active: false, startX: 0, startY: 0, originX: 0, originY: 0 });

const cropStyle = computed(() => ({
  left: `${(crop.x / natural.width) * display.width}px`,
  top: `${(crop.y / natural.height) * display.height}px`,
  width: `${(crop.width / natural.width) * display.width}px`,
  height: `${(crop.height / natural.height) * display.height}px`,
}));

watch(() => props.open, (value) => {
  if (!value) reset();
});

function reset() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = '';
  sourceFile.value = null;
  ready.value = false;
  processing.value = false;
}

function close() {
  emit('close');
}

function onFile(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  sourceFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
  ready.value = false;
  event.target.value = '';
}

function onImageLoad() {
  const img = imageEl.value;
  if (!img) return;
  natural.width = img.naturalWidth;
  natural.height = img.naturalHeight;
  display.width = img.clientWidth;
  display.height = img.clientHeight;
  const size = Math.min(natural.width, natural.height, 400);
  crop.x = Math.floor((natural.width - size) / 2);
  crop.y = Math.floor((natural.height - size) / 2);
  crop.width = size;
  crop.height = size;
  ready.value = true;
}

function startDrag(event) {
  drag.active = true;
  drag.startX = event.clientX;
  drag.startY = event.clientY;
  drag.originX = crop.x;
  drag.originY = crop.y;
  window.addEventListener('pointermove', onDrag);
  window.addEventListener('pointerup', endDrag);
}

function onDrag(event) {
  if (!drag.active) return;
  const scaleX = natural.width / display.width;
  const scaleY = natural.height / display.height;
  const nextX = drag.originX + (event.clientX - drag.startX) * scaleX;
  const nextY = drag.originY + (event.clientY - drag.startY) * scaleY;
  crop.x = Math.max(0, Math.min(natural.width - crop.width, Math.round(nextX)));
  crop.y = Math.max(0, Math.min(natural.height - crop.height, Math.round(nextY)));
}

function endDrag() {
  drag.active = false;
  window.removeEventListener('pointermove', onDrag);
  window.removeEventListener('pointerup', endDrag);
}

async function applyCrop() {
  if (!imageEl.value || !sourceFile.value) return;
  processing.value = true;
  try {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(crop.width));
    canvas.height = Math.max(1, Math.round(crop.height));
    const ctx = canvas.getContext('2d');
    ctx.drawImage(
      imageEl.value,
      crop.x,
      crop.y,
      crop.width,
      crop.height,
      0,
      0,
      canvas.width,
      canvas.height
    );
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png', 0.92));
    if (!blob) throw new Error('Unable to crop image');
    const file = new File([blob], `${sourceFile.value.name.replace(/\.[^.]+$/, '')}-cropped.png`, { type: 'image/png' });
    emit('cropped', {
      file,
      crop: { ...crop },
      width: canvas.width,
      height: canvas.height,
    });
    close();
  } finally {
    processing.value = false;
  }
}
</script>
