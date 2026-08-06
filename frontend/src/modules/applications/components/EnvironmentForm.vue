<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Environment name</label>
        <input v-model="form.name" type="text" class="input" required />
        <p v-if="errors.name" class="mt-1 text-xs text-rose-600">{{ errors.name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Type</label>
        <select v-model="form.type" class="input" required :disabled="Boolean(initial.uuid)">
          <option value="development">Development</option>
          <option value="testing">Testing</option>
          <option value="staging">Staging</option>
          <option value="production">Production</option>
          <option value="sandbox">Sandbox</option>
        </select>
        <p v-if="errors.type" class="mt-1 text-xs text-rose-600">{{ errors.type[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input v-model="form.slug" type="text" class="input" placeholder="auto-generated if empty" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">API URL</label>
        <input v-model="form.api_url" type="url" class="input" placeholder="https://" />
        <p v-if="errors.api_url" class="mt-1 text-xs text-rose-600">{{ errors.api_url[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Web URL</label>
        <input v-model="form.web_url" type="url" class="input" placeholder="https://" />
        <p v-if="errors.web_url" class="mt-1 text-xs text-rose-600">{{ errors.web_url[0] }}</p>
      </div>
      <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.is_current" type="checkbox" class="rounded border-slate-300" />
          Set as current environment
        </label>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">Environment variables</h3>
        <button type="button" class="text-xs font-medium text-brand-700 hover:underline" @click="addVariable">Add variable</button>
      </div>
      <p class="mb-3 text-xs text-slate-500">Values are encrypted at rest and masked in API responses.</p>
      <div class="space-y-2">
        <div v-for="(item, index) in form.variables" :key="index" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
          <input v-model="item.key" type="text" class="input" placeholder="KEY_NAME" />
          <input v-model="item.value" type="text" class="input" :placeholder="item.has_value ? '******** (leave blank to keep)' : 'value'" />
          <button type="button" class="h-12 rounded-[12px] border border-slate-300 px-3 text-xs text-rose-700 hover:bg-rose-50" @click="removeVariable(index)">Remove</button>
        </div>
        <p v-if="!form.variables.length" class="text-sm text-slate-500">No variables configured.</p>
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
  const variables = (value.variables || []).map((item) => ({
    key: item.key || '',
    value: '',
    has_value: Boolean(item.has_value),
    keep_existing: true,
  }));

  return {
    name: value.name || '',
    slug: value.slug || '',
    type: value.type || 'development',
    api_url: value.api_url || '',
    web_url: value.web_url || '',
    status: value.status || 'active',
    is_current: Boolean(value.is_current),
    variables,
  };
}

function addVariable() {
  form.variables.push({ key: '', value: '', has_value: false, keep_existing: false });
}

function removeVariable(index) {
  form.variables.splice(index, 1);
}

function onSubmit() {
  const payload = {
    name: form.name,
    slug: form.slug || null,
    type: form.type,
    api_url: form.api_url || null,
    web_url: form.web_url || null,
    status: form.status,
    is_current: form.is_current,
    variables: form.variables
      .filter((item) => item.key.trim())
      .map((item) => ({
        key: item.key.trim(),
        value: item.value,
        keep_existing: !item.value && item.has_value,
      })),
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
