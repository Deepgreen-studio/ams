<template>
  <form class="space-y-5" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-name">Name</label>
        <input
          id="tag-name"
          v-model="form.name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          required
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-slug">SEO slug</label>
        <input
          id="tag-slug"
          v-model="form.slug"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 font-mono text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="auto-generated if empty"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-sort"
          >Sort order</label
        >
        <input
          id="tag-sort"
          v-model.number="form.sort_order"
          type="number"
          min="0"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-status"
          >Status</label
        >
        <select
          id="tag-status"
          v-model="form.is_active"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        >
          <option :value="true">Active</option>
          <option :value="false">Inactive</option>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-description"
          >Description</label
        >
        <textarea
          id="tag-description"
          v-model="form.description"
          rows="3"
          class="min-h-[5rem] w-full resize-y rounded-[12px] border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="Optional short description"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-seo-title"
          >SEO title</label
        >
        <input
          id="tag-seo-title"
          v-model="form.seo_title"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-seo-description"
          >SEO description</label
        >
        <input
          id="tag-seo-description"
          v-model="form.seo_description"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
    </div>

    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
      <button
        type="button"
        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});
const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
  name: '',
  slug: '',
  description: '',
  seo_title: '',
  seo_description: '',
  is_active: true,
  sort_order: 0,
});

watch(
  () => props.initial,
  (value) => {
    form.name = value?.name || '';
    form.slug = value?.slug || '';
    form.description = value?.description || '';
    form.seo_title = value?.seo_title || '';
    form.seo_description = value?.seo_description || '';
    form.is_active = value?.is_active ?? true;
    form.sort_order = value?.sort_order ?? 0;
  },
  { immediate: true, deep: true },
);

function onSubmit() {
  emit('submit', {
    name: form.name,
    slug: form.slug || null,
    description: form.description || null,
    seo_title: form.seo_title || null,
    seo_description: form.seo_description || null,
    is_active: form.is_active,
    sort_order: form.sort_order,
  });
}
</script>
