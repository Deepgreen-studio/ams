<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
        <input
          v-model="form.display_name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
        <p v-if="errors.display_name" class="mt-1 text-xs text-rose-600">{{ errors.display_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Machine name</label>
        <input
          v-model="form.name"
          type="text"
          :disabled="lockName"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 disabled:bg-slate-50"
          placeholder="company-ops"
        />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" :disabled="loading" @click="$emit('cancel')">
        Cancel
      </button>
      <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="loading">
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save role' },
  lockName: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  (value) => Object.assign(form, createForm(value)),
  { deep: true }
);

function createForm(value = {}) {
  return {
    name: value.name || '',
    display_name: value.display_name || '',
    description: value.description || '',
  };
}

function onSubmit() {
  emit('submit', { ...form });
}
</script>
