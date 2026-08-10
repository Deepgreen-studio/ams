<template>
  <div class="flex flex-col items-center text-center">
    <div class="relative">
      <UserAvatar
        :src="preview || user?.avatar_url || ''"
        :name="user?.full_name || user?.name || 'User'"
        :first-name="user?.first_name || ''"
        :last-name="user?.last_name || ''"
        size="2xl"
        class="ring-4 ring-white shadow-md shadow-slate-200/70"
      />
      <label
        class="absolute bottom-1 right-1 inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"
        title="Choose photo"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5A1.5 1.5 0 0 1 4.5 7h2.1l1.2-1.8A1.5 1.5 0 0 1 9 4.5h6a1.5 1.5 0 0 1 1.2.7L17.4 7h2.1A1.5 1.5 0 0 1 21 8.5v9A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-9Z" />
          <circle cx="12" cy="13" r="3.25" />
        </svg>
        <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onFileChange" />
      </label>
    </div>

    <div class="mt-4 space-y-1">
      <p class="text-sm font-semibold text-slate-900">Profile Photo</p>
      <p class="text-xs text-slate-500">JPG, PNG or WebP · Max 2MB</p>
      <p v-if="fileName" class="truncate text-xs text-slate-600">{{ fileName }}</p>
    </div>

    <div class="mt-4 flex w-full max-w-xs gap-2">
      <label
        class="flex-1 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
      >
        Choose file
        <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onFileChange" />
      </label>
      <button
        type="button"
        class="flex-1 rounded-xl bg-brand-600 px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="!file || loading"
        @click="$emit('upload', file)"
      >
        {{ loading ? 'Uploading…' : 'Upload' }}
      </button>
    </div>

    <p v-if="error" class="mt-3 text-xs text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import UserAvatar from '@/components/ui/UserAvatar.vue';

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

const fileName = computed(() => file.value?.name || '');

watch(
  () => props.user?.avatar_url,
  () => {
    preview.value = '';
    file.value = null;
  },
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
