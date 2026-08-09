<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <UserAvatar
        :src="preview || user?.avatar_url || ''"
        :name="user?.full_name || user?.name || 'User'"
        :first-name="user?.first_name || ''"
        :last-name="user?.last_name || ''"
        size="xl"
        class="ring-2 ring-white"
      />
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
import { ref, watch } from 'vue';
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
