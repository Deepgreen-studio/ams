<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-slate-700">Featured image</label>

    <div
      class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
      :class="modelValue ? '' : 'border-dashed'"
    >
      <img
        v-if="modelValue"
        :src="modelValue"
        alt=""
        class="h-44 w-full object-cover"
      />
      <div
        v-else
        class="flex h-32 flex-col items-center justify-center gap-1 px-4 text-center"
      >
        <p class="text-sm font-medium text-slate-600">No featured image</p>
        <p class="text-xs text-slate-400">Upload, browse the library, or paste a URL</p>
      </div>
    </div>

    <div class="flex flex-wrap gap-2">
      <label
        class="cursor-pointer h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
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
        class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="pickerOpen = true"
      >
        Browse library
      </button>
      <button
        v-if="modelValue"
        type="button"
        class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
        @click="$emit('update:modelValue', '')"
      >
        Clear
      </button>
    </div>

    <input
      :value="modelValue"
      type="url"
      class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
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
