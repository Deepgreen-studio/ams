<template>
  <div class="space-y-3">
    <div class="flex items-center gap-4">
      <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-xl bg-slate-100">
        <img v-if="preview || company?.logo_url" :src="preview || company?.logo_url" alt="Logo" class="h-full w-full object-cover" />
        <span v-else class="text-xs text-slate-500">No logo</span>
      </div>
      <div>
        <p class="text-sm font-medium text-slate-900">Company logo</p>
        <p class="text-xs text-slate-500">JPG, PNG or WebP. Max 2MB.</p>
        <div class="mt-2 flex gap-2">
          <label class="cursor-pointer rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
            Choose file
            <input type="file" accept="image/*" class="hidden" @change="onChange" />
          </label>
          <button type="button" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="!file || loading" @click="$emit('upload', file)">
            {{ loading ? 'Uploading...' : 'Upload' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  company: { type: Object, default: null },
  loading: { type: Boolean, default: false },
});
defineEmits(['upload']);

const file = ref(null);
const preview = ref('');

watch(() => props.company?.logo_url, () => {
  preview.value = '';
  file.value = null;
});

function onChange(event) {
  const selected = event.target.files?.[0];
  file.value = selected || null;
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = selected ? URL.createObjectURL(selected) : '';
}
</script>
