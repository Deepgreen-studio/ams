<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Version number (MAJOR.MINOR.PATCH)</label>
        <input v-model="form.version_number" type="text" class="input" placeholder="1.0.0" :required="!initial.uuid" />
        <p class="mt-1 text-xs text-slate-500">Semantic versioning. Optional leading "v" is accepted.</p>
        <p v-if="errors.version_number" class="mt-1 text-xs text-rose-600">{{ errors.version_number[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Build number</label>
        <input v-model="form.build_number" type="text" class="input" placeholder="100" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="draft">Draft</option>
          <option value="testing">Testing</option>
          <option value="beta">Beta</option>
          <option value="production">Production</option>
          <option value="deprecated">Deprecated</option>
          <option value="rollback">Rollback</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Release date</label>
        <input v-model="form.release_date" type="datetime-local" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Minimum supported version</label>
        <input v-model="form.minimum_supported_version" type="text" class="input" placeholder="1.0.0" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Release notes</label>
        <textarea v-model="form.release_notes" rows="5" class="input" placeholder="What's new in this version..." />
      </div>
    </div>
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="$emit('cancel')">Cancel</button>
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
  submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit', 'cancel']);
const form = reactive(createForm(props.initial));
watch(() => props.initial, (value) => Object.assign(form, createForm(value)), { deep: true });

function createForm(value = {}) {
  return {
    version_number: value.version_number || '',
    build_number: value.build_number || '',
    status: value.status || 'draft',
    release_date: toLocalInput(value.release_date),
    minimum_supported_version: value.minimum_supported_version || '',
    release_notes: value.release_notes || '',
  };
}

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function onSubmit() {
  const payload = {
    version_number: form.version_number,
    build_number: form.build_number || null,
    status: form.status,
    release_date: form.release_date ? new Date(form.release_date).toISOString() : null,
    minimum_supported_version: form.minimum_supported_version || null,
    release_notes: form.release_notes || null,
  };
  emit('submit', payload);
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
