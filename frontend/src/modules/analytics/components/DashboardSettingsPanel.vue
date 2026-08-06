<template>
  <div class="space-y-4">
    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Name</label>
      <input v-model="form.name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div>
      <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
      <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Visibility</label>
        <select v-model="form.visibility" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="personal">Personal</option>
          <option value="company">Company</option>
          <option value="role">Role-based</option>
          <option value="shared">Shared</option>
          <option value="template">Template</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
        <select v-model="form.status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
        <select v-model="form.category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option v-for="category in categories" :key="category.value" :value="category.value">
            {{ category.label }}
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Auto refresh (sec)</label>
        <input
          v-model.number="form.settings.auto_refresh_seconds"
          type="number"
          min="30"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
        />
      </div>
    </div>
    <div class="flex flex-wrap gap-4">
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_default" type="checkbox" class="rounded border-slate-300" />
        Default dashboard
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_template" type="checkbox" class="rounded border-slate-300" />
        Save as template
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.settings.show_filters" type="checkbox" class="rounded border-slate-300" />
        Show filters
      </label>
    </div>
    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="emit('cancel')">
        Cancel
      </button>
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
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

const props = defineProps({
  dashboard: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'cancel']);

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
