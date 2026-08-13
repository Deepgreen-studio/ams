<template>
  <div class="space-y-4">
    <div>
      <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
      <input v-model="form.name" class="input" />
    </div>
    <div>
      <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
      <textarea v-model="form.description" rows="3" class="input" />
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Visibility</label>
        <SelectBox v-model="form.visibility" :options="visibilityOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
        <SelectBox v-model="form.status" :options="statusOptions" />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
        <SelectBox v-model="form.category" :options="categories" />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Auto refresh (sec)</label>
        <input
          v-model.number="form.settings.auto_refresh_seconds"
          type="number"
          min="30"
          class="input"
        />
      </div>
    </div>
    <div class="flex flex-wrap gap-4">
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_default" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
        Default dashboard
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_template" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
        Save as template
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.settings.show_filters" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
        Show filters
      </label>
    </div>
    <div class="flex justify-end gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="saving"
        @click="emit('save', { ...form, settings: { ...form.settings } })"
      >
        Save settings
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  dashboard: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'cancel']);

const visibilityOptions = [
  { value: 'personal', label: 'Personal' },
  { value: 'company', label: 'Company' },
  { value: 'role', label: 'Role-based' },
  { value: 'shared', label: 'Shared' },
  { value: 'template', label: 'Template' },
];

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'archived', label: 'Archived' },
];

const form = reactive({
  name: '',
  description: '',
  visibility: 'personal',
  status: 'draft',
  category: 'business',
  is_default: false,
  is_template: false,
  settings: {
    auto_refresh_seconds: 300,
    show_filters: true,
  },
});

watch(
  () => props.dashboard,
  (value) => {
    if (!value) return;
    form.name = value.name || '';
    form.description = value.description || '';
    form.visibility = value.visibility || 'personal';
    form.status = value.status || 'draft';
    form.category = value.category || 'business';
    form.is_default = Boolean(value.is_default);
    form.is_template = Boolean(value.is_template);
    form.settings = {
      auto_refresh_seconds: value.settings?.auto_refresh_seconds || 300,
      show_filters: value.settings?.show_filters !== false,
    };
  },
  { immediate: true, deep: true }
);
</script>
