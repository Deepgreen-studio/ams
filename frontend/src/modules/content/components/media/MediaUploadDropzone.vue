<template>
  <div
    class="rounded-[12px] border border-dashed border-zinc-300 bg-zinc-50/70 p-8 text-center transition"
    :class="{ 'border-brand-400 bg-brand-50': dragging }"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="onDrop"
  >
    <p class="text-sm font-medium text-slate-800">Drag & drop files here</p>
    <p class="mt-1 text-xs text-slate-500">Images, videos, PDF, Word, Excel, ZIP</p>
    <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
      <label
        class="inline-flex cursor-pointer rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Browse files
        <input type="file" multiple class="hidden" :accept="accept" @change="onSelect" />
      </label>
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="$emit('crop')"
      >
        Upload & crop image
      </button>
    </div>
    <div
      v-if="progress > 0 && progress < 100"
      class="mx-auto mt-4 h-2 w-48 overflow-hidden rounded-full bg-zinc-200"
    >
      <div class="h-full bg-brand-600 transition-all" :style="{ width: `${progress}%` }" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  progress: { type: Number, default: 0 },
  accept: {
    type: String,
    default: 'image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.csv',
  },
});

const emit = defineEmits(['files', 'crop']);
const dragging = ref(false);

function onDrop(event) {
  dragging.value = false;
  emit('files', event.dataTransfer.files);
}

function onSelect(event) {
  emit('files', event.target.files);
  event.target.value = '';
}
</script>
