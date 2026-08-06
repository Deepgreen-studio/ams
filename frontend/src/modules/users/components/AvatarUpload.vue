<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-xl font-semibold text-brand-700 ring-2 ring-white">
        <img v-if="preview || user?.avatar_url" :src="preview || user?.avatar_url" alt="Avatar" class="h-full w-full object-cover" />
        <span v-else>{{ initials }}</span>
      </div>
      <div>
        <p class="text-sm font-medium text-slate-900">Profile photo</p>
        <p class="text-xs text-slate-500">JPG, PNG or WebP. Max 2MB.</p>
        <div class="mt-2 flex gap-2">
          <label class="cursor-pointer rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
            Choose file
            <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onFileChange" />
          </label>
          <button
            type="button"
            class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="!file || loading"
            @click="$emit('upload', file)"
          >
            {{ loading ? 'Uploading...' : 'Upload' }}
          </button>
        </div>
        <p v-if="error" class="mt-2 text-xs text-rose-600">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
});

defineEmits(['upload']);

const file = ref(null);
const preview = ref('');

const initials = computed(() => {
  const first = props.user?.first_name?.[0] || '';
  const last = props.user?.last_name?.[0] || '';
  return `${first}${last}`.toUpperCase() || 'U';
});

watch(
  () => props.user?.avatar_url,
  () => {
    preview.value = '';
    file.value = null;
  }
);

function onFileChange(event) {
  const selected = event.target.files?.[0];
  file.value = selected || null;

  if (preview.value) {
    URL.revokeObjectURL(preview.value);
  }

  preview.value = selected ? URL.createObjectURL(selected) : '';
}
</script>
