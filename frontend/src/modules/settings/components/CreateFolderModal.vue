<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="settings-create-folder-title"
    >
      <h3 id="settings-create-folder-title" class="text-lg font-semibold text-slate-900">
        New folder
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        Create a folder to organize media library files.
      </p>

      <form class="mt-5 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label
            for="settings-folder-name"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >
            Folder name
          </label>
          <input
            id="settings-folder-name"
            ref="nameInput"
            v-model="name"
            type="text"
            maxlength="255"
            required
            autocomplete="off"
            class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200"
            :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-200': Boolean(error) }"
            placeholder="e.g. Product images"
            :disabled="loading"
            @keydown.esc.prevent="onCancel"
          />
          <p v-if="error" class="mt-1.5 text-sm text-rose-600">{{ error }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="onCancel"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="loading || !name.trim()"
          >
            {{ loading ? 'Creating…' : 'Create folder' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const name = ref('');
const nameInput = ref(null);

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    name.value = '';
    await nextTick();
    nameInput.value?.focus();
  }
);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit() {
  const trimmed = name.value.trim();
  if (!trimmed || props.loading) return;
  emit('submit', { name: trimmed });
}
</script>
