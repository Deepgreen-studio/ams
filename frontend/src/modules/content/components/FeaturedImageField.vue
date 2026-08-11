<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-slate-700">Featured image</label>

    <div
      class="overflow-hidden rounded-[12px] bg-zinc-50 ring-1 ring-zinc-100"
      :class="modelValue ? '' : 'border border-dashed border-zinc-300 bg-transparent'"
    >
      <div v-if="modelValue" class="relative">
        <img :src="modelValue" alt="" class="h-48 w-full object-cover" />
        <div
          class="absolute inset-x-0 bottom-0 flex flex-wrap items-center gap-2 bg-gradient-to-t from-black/55 to-transparent px-3 pb-3 pt-8"
        >
          <label
            class="cursor-pointer rounded-[10px] bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-white"
          >
            {{ uploading ? 'Uploading…' : 'Replace' }}
            <input
              type="file"
              accept="image/*"
              class="hidden"
              :disabled="uploading"
              @change="onUpload"
            />
          </label>
          <button
            type="button"
            class="rounded-[10px] bg-white/95 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-white"
            @click="pickerOpen = true"
          >
            Browse library
          </button>
          <button
            type="button"
            class="rounded-[10px] bg-rose-500/90 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-500"
            @click="$emit('update:modelValue', '')"
          >
            Clear
          </button>
        </div>
      </div>

      <div v-else class="px-4 py-8 text-center">
        <div
          class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-[10px] bg-zinc-100 text-slate-400"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"
            />
          </svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No featured image</p>
        <p class="mt-1 text-xs text-slate-400">Upload, browse the library, or paste a URL</p>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
          <label
            class="cursor-pointer rounded-[12px] border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          >
            {{ uploading ? 'Uploading…' : 'Upload image' }}
            <input
              type="file"
              accept="image/*"
              class="hidden"
              :disabled="uploading"
              @change="onUpload"
            />
          </label>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="pickerOpen = true"
          >
            Browse library
          </button>
        </div>
      </div>
    </div>

    <input
      :value="modelValue"
      type="url"
      class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-zinc-400 focus:border-brand-500"
      placeholder="https://… image URL"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" class="text-xs text-rose-600">{{ error }}</p>
    <MediaPickerModal :open="pickerOpen" @close="pickerOpen = false" @select="onPick" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import MediaPickerModal from '@/modules/content/components/media/MediaPickerModal.vue';
import { contentService } from '@/modules/content/services/contentService';

defineProps({
  modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const uploading = ref(false);
const error = ref('');
const pickerOpen = ref(false);

async function onUpload(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;

  uploading.value = true;
  error.value = '';
  try {
    const formData = new FormData();
    formData.append('file', file);
    const { data } = await contentService.uploadMedia(formData);
    emit('update:modelValue', data.data?.media?.url || '');
  } catch (err) {
    error.value = err?.message || 'Unable to upload featured image';
  } finally {
    uploading.value = false;
  }
}

function onPick(item) {
  emit('update:modelValue', item?.url || '');
}
</script>
