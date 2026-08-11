<template>
  <form class="space-y-5" novalidate @submit.prevent="onSubmit">
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-name">Name</label>
        <input
          id="tag-name"
          v-model="form.name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('name')"
        />
        <p v-if="displayErrors.name" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.name[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-slug">SEO slug</label>
        <input
          id="tag-slug"
          v-model="form.slug"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 font-mono text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('slug')"
          placeholder="auto-generated if empty"
        />
        <p v-if="displayErrors.slug" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.slug[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-sort">
          Sort order
        </label>
        <input
          id="tag-sort"
          v-model.number="form.sort_order"
          type="number"
          min="0"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('sort_order')"
        />
        <p v-if="displayErrors.sort_order" class="mt-1 text-xs text-rose-600">
          {{ displayErrors.sort_order[0] }}
        </p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-status">
          Status
        </label>
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
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-description">
          Description
        </label>
        <textarea
          id="tag-description"
          v-model="form.description"
          rows="3"
          class="min-h-[5rem] w-full resize-y rounded-[12px] border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="Optional short description"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-seo-title">
          SEO title
        </label>
        <input
          id="tag-seo-title"
          v-model="form.seo_title"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tag-seo-description">
          SEO description
        </label>
        <input
          id="tag-seo-description"
          v-model="form.seo_description"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
      <button
        type="button"
        class="rounded-[12px] border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
  initial: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});
const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();
const localErrors = ref({});

const form = reactive({
  name: '',
  slug: '',
  description: '',
  seo_title: '',
  seo_description: '',
  is_active: true,
  sort_order: 0,
});

const displayErrors = computed(() => ({
  ...localErrors.value,
  ...props.errors,
}));

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
    localErrors.value = {};
  },
  { immediate: true, deep: true },
);

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true }
);

function fieldClass(field) {
  return displayErrors.value?.[field]
    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100'
    : '';
}

function validate() {
  const next = {};

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (form.slug && !/^[A-Za-z0-9_-]+$/.test(form.slug)) {
    next.slug = ['The slug may only contain letters, numbers, dashes, and underscores.'];
  }

  if (
    form.sort_order !== null &&
    form.sort_order !== undefined &&
    form.sort_order !== '' &&
    (!Number.isInteger(Number(form.sort_order)) || Number(form.sort_order) < 0)
  ) {
    next.sort_order = ['Sort order must be 0 or greater.'];
  }

  localErrors.value = next;
  return Object.keys(next).length === 0;
}

function onSubmit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  localErrors.value = {};
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
