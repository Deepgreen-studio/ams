<template>
  <div class="flex flex-col items-center text-center">
    <div class="relative">
      <div
        class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-[20px] bg-brand-50 text-xl font-semibold text-brand-700 ring-1 ring-zinc-100"
      >
        <img
          v-if="preview || company?.logo_url"
          :src="preview || company?.logo_url"
          alt="Company logo"
          class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
      </div>
      <label
        class="absolute -bottom-1 -right-1 inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-white text-slate-600 ring-1 ring-zinc-100 transition hover:bg-brand-50 hover:text-brand-700"
        title="Choose logo"
      >
        <svg
          class="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 8.5A1.5 1.5 0 0 1 4.5 7h2.1l1.2-1.8A1.5 1.5 0 0 1 9 4.5h6a1.5 1.5 0 0 1 1.2.7L17.4 7h2.1A1.5 1.5 0 0 1 21 8.5v9A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-9Z"
          />
          <circle cx="12" cy="13" r="3.25" />
        </svg>
        <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onChange" />
      </label>
    </div>

    <div class="mt-4 space-y-1">
      <p class="text-sm font-semibold text-slate-900">Company logo</p>
      <p class="text-xs text-slate-500">JPG, PNG or WebP · Max 2MB</p>
      <p v-if="fileName" class="truncate text-xs text-slate-600">{{ fileName }}</p>
    </div>

    <div class="mt-4 flex w-full max-w-xs gap-2">
      <label
        class="flex-1 cursor-pointer rounded-[12px] bg-slate-50 px-3 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
      >
        Choose file
        <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onChange" />
      </label>
      <button
        type="button"
        class="flex-1 rounded-[12px] bg-brand-600 px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!file || loading"
        @click="$emit('upload', file)"
      >
        {{ loading ? 'Uploading…' : 'Upload' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  company: { type: Object, default: null },
  loading: { type: Boolean, default: false },
});

defineEmits(['upload']);

const file = ref(null);
const preview = ref('');

const fileName = computed(() => file.value?.name || '');
const initials = computed(() => (props.company?.company_name || 'C').slice(0, 2).toUpperCase());

watch(
  () => props.company?.logo_url,
  () => {
    preview.value = '';
    file.value = null;
  },
);

function onChange(event) {
  const selected = event.target.files?.[0];
  file.value = selected || null;
  if (preview.value) {
    URL.revokeObjectURL(preview.value);
  }
  preview.value = selected ? URL.createObjectURL(selected) : '';
}
</script>
