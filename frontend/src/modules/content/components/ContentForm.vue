<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Content type</label>
        <select v-model="form.content_type_id" class="input" required>
          <option value="" disabled>Select type</option>
          <option v-for="type in types" :key="type.uuid" :value="type.uuid">{{ type.name }}</option>
        </select>
        <p v-if="errors.content_type_id" class="mt-1 text-xs text-rose-600">{{ errors.content_type_id[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select v-model="form.status" class="input">
          <option v-for="status in statuses" :key="status.uuid" :value="status.slug">{{ status.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input v-model="form.title" type="text" class="input" required />
        <p v-if="errors.title" class="mt-1 text-xs text-rose-600">{{ errors.title[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input v-model="form.slug" type="text" class="input" placeholder="auto-generated if empty" />
        <p v-if="errors.slug" class="mt-1 text-xs text-rose-600">{{ errors.slug[0] }}</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Categories</label>
        <select v-model="form.categories" class="input min-h-28" multiple>
          <option v-for="category in categories" :key="category.uuid" :value="category.uuid">{{ category.name }}</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Hold Ctrl/Cmd to select multiple categories.</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Featured image URL</label>
        <input v-model="form.featured_image" type="url" class="input" placeholder="https://" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Excerpt</label>
        <textarea v-model="form.excerpt" rows="2" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Body</label>
        <textarea v-model="form.body" rows="8" class="input font-mono text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">SEO title</label>
        <input v-model="form.seo_title" type="text" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">SEO keywords</label>
        <input v-model="form.seo_keywords" type="text" class="input" placeholder="comma separated" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">SEO description</label>
        <textarea v-model="form.seo_description" rows="2" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Tags</label>
        <input v-model="form.tagsInput" type="text" class="input" placeholder="Comma-separated tags" />
        <p class="mt-1 text-xs text-slate-500">New tag names are created automatically when saved.</p>
      </div>
      <div class="flex items-center gap-2">
        <input id="is_featured" v-model="form.is_featured" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
        <label for="is_featured" class="text-sm font-medium text-slate-700">Featured content</label>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Sort order</label>
        <input v-model.number="form.sort_order" type="number" min="0" class="input" />
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
  types: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  error: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
  content_type_id: '',
  categories: [],
  title: '',
  slug: '',
  excerpt: '',
  body: '',
  featured_image: '',
  seo_title: '',
  seo_description: '',
  seo_keywords: '',
  status: 'draft',
  is_featured: false,
  sort_order: 0,
  tagsInput: '',
});

watch(
  () => props.initial,
  (value) => {
    form.content_type_id = value?.type?.uuid || value?.content_type_id || '';
    form.categories = Array.isArray(value?.categories) && value.categories.length
      ? value.categories.map((category) => category.uuid || category)
      : value?.category?.uuid
        ? [value.category.uuid]
        : [];
    form.title = value?.title || '';
    form.slug = value?.slug || '';
    form.excerpt = value?.excerpt || '';
    form.body = value?.body || '';
    form.featured_image = value?.featured_image || '';
    form.seo_title = value?.seo_title || '';
    form.seo_description = value?.seo_description || '';
    form.seo_keywords = value?.seo_keywords || '';
    form.status = value?.status?.slug || value?.status || 'draft';
    form.is_featured = Boolean(value?.is_featured);
    form.sort_order = value?.sort_order ?? 0;
    form.tagsInput = Array.isArray(value?.tags) ? value.tags.map((tag) => tag.name || tag).join(', ') : '';
  },
  { immediate: true, deep: true }
);

function onSubmit() {
  const tags = form.tagsInput
    .split(',')
    .map((tag) => tag.trim())
    .filter(Boolean);

  emit('submit', {
    content_type_id: form.content_type_id,
    categories: form.categories,
    content_category_id: form.categories[0] || null,
    title: form.title,
    slug: form.slug || null,
    excerpt: form.excerpt || null,
    body: form.body || null,
    featured_image: form.featured_image || null,
    seo_title: form.seo_title || null,
    seo_description: form.seo_description || null,
    seo_keywords: form.seo_keywords || null,
    status: form.status,
    is_featured: form.is_featured,
    sort_order: form.sort_order,
    tags,
  });
}
</script>
