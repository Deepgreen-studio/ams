<template>
  <div
    class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center transition"
    :class="{ 'border-brand-400 bg-brand-50': dragging }"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="onDrop"
  >
    <p class="text-sm font-medium text-slate-800">Drag & drop files here</p>
    <p class="mt-1 text-xs text-slate-500">or choose files to upload</p>
    <label class="mt-4 inline-flex cursor-pointer rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
      Browse files
      <input type="file" multiple class="hidden" @change="onSelect" />
    </label>
    <div v-if="progress > 0 && progress < 100" class="mx-auto mt-4 h-2 w-48 overflow-hidden rounded-full bg-slate-200">
      <div class="h-full bg-brand-600 transition-all" :style="{ width: `${progress}%` }" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  progress: { type: Number, default: 0 },
});
const emit = defineEmits(['files']);
const dragging = ref(false);

function onDrop(event) {
  dragging.value = false;
  emit('files', event.dataTransfer.files);
}

function onSelect(event) {
  emit('files', event.target.files);
}
</script>
