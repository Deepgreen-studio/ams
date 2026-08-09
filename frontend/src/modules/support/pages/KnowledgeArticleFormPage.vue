<template>
  <div>
    <!-- <PageHeader
      :title="isEdit ? 'Edit article' : 'Create article'"
      description="Author knowledge content or sync from CMS"
    /> -->
    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form class="max-w-3xl space-y-4 rounded-xl border border-slate-200 bg-white p-6" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Title</label>
        <input v-model="form.title" required class="input" />
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Type</label>
          <select v-model="form.type" class="input" required>
            <option value="article">Article</option>
            <option value="guide">Guide</option>
            <option value="faq">FAQ</option>
            <option value="tutorial">Tutorial</option>
            <option value="video">Video</option>
            <option value="release_notes">Release Notes</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Category</label>
          <select v-model="form.category_id" class="input">
            <option value="">None</option>
            <option v-for="category in flatCategories" :key="category.uuid" :value="category.uuid">
              {{ category.name }}
            </option>
          </select>
        </div>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Summary</label>
        <textarea v-model="form.summary" rows="2" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Body (HTML)</label>
        <textarea v-model="form.body" rows="10" class="input font-mono text-xs" />
      </div>
      <div v-if="form.type === 'video'">
        <label class="mb-1 block text-xs font-medium text-slate-600">Video URL</label>
        <input v-model="form.video_url" type="url" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Tags (comma separated)</label>
        <input v-model="tagsInput" class="input" placeholder="setup, security" />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">CMS content UUID (optional)</label>
        <input v-model="form.content_id" class="input" />
        <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
          <input v-model="form.sync_from_cms" type="checkbox" />
          Sync title/body from CMS on save
        </label>
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.is_featured" type="checkbox" />
        Featured
      </label>
      <div class="flex justify-end gap-2">
        <RouterLink
          :to="{ name: 'support.knowledge.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm"
        >
          Cancel
        </RouterLink>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const tagsInput = ref('');

const form = reactive({
  title: '',
  type: 'article',
  category_id: '',
  summary: '',
  body: '',
  video_url: '',
  content_id: '',
  sync_from_cms: false,
  is_featured: false,
});

const flatCategories = computed(() => {
  const items = [];
  for (const category of store.categories || []) {
    items.push(category);
    for (const child of category.children || []) {
      items.push({ ...child, name: `${category.name} / ${child.name}` });
    }
  }
  return items;
});

onMounted(async () => {
  await store.fetchCategories();
  if (isEdit.value) {
    const article = await store.fetchArticle(route.params.id);
    Object.assign(form, {
      title: article.title || '',
      type: article.type || 'article',
      category_id: article.category?.uuid || '',
      summary: article.summary || '',
      body: article.body || '',
      video_url: article.video_url || '',
      content_id: article.content?.uuid || '',
      sync_from_cms: Boolean(article.sync_from_cms),
      is_featured: Boolean(article.is_featured),
    });
    tagsInput.value = (article.tags || []).map((t) => t.name).join(', ');
  }
});

async function submit() {
  const payload = {
    ...form,
    category_id: form.category_id || null,
    content_id: form.content_id || null,
    tags: tagsInput.value
      .split(',')
      .map((t) => t.trim())
      .filter(Boolean),
  };

  const article = isEdit.value
    ? await store.updateArticle(route.params.id, payload)
    : await store.createArticle(payload);

  await router.push({ name: 'support.knowledge.show', params: { id: article.uuid } });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
