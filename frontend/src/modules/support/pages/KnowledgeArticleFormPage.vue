<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="cancelTo"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Cancel
      </RouterLink>
      <button
        type="submit"
        form="knowledge-article-form"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
      >
        {{ store.saving ? 'Saving…' : 'Save' }}
      </button>
    </Teleport>

    <SupportSubnav />

    <form
      id="knowledge-article-form"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
      @submit.prevent="submit"
    >
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">
          {{ isEdit ? 'Edit article' : 'Create knowledge article' }}
        </h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Author knowledge content or sync from CMS.
        </p>
      </div>

      <div class="space-y-5 px-6 py-6 sm:px-8">
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Title</label>
          <input
            v-model="form.title"
            required
            class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Type</label>
            <SelectBox v-model="form.type" :options="typeOptions" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600">Category</label>
            <SelectBox v-model="form.category_id" :options="categoryOptions" />
          </div>
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Summary</label>
          <textarea
            v-model="form.summary"
            rows="3"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Body (HTML)</label>
          <textarea
            v-model="form.body"
            rows="10"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 font-mono text-xs text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div v-if="form.type === 'video'">
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Video URL</label>
          <input
            v-model="form.video_url"
            type="url"
            class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">Tags (comma separated)</label>
          <input
            v-model="tagsInput"
            class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="setup, security"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-600">CMS content UUID (optional)</label>
          <input
            v-model="form.content_id"
            class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
          />
          <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
            <input v-model="form.sync_from_cms" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
            Sync title/body from CMS on save
          </label>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.is_featured" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
          Featured
        </label>
      </div>

      <div class="flex justify-end gap-2 border-t border-zinc-100 px-6 py-4 sm:px-8">
        <RouterLink
          :to="cancelTo"
          class="inline-flex h-10 items-center rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Cancel
        </RouterLink>
        <button
          type="submit"
          class="inline-flex h-10 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useKnowledgeBaseStore } from '@/modules/support/stores/knowledgeBase';

const store = useKnowledgeBaseStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const isEdit = computed(() => Boolean(route.params.id));
const tagsInput = ref('');

const cancelTo = computed(() =>
  isEdit.value
    ? { name: 'support.knowledge.show', params: { id: route.params.id } }
    : { name: 'support.knowledge.index' }
);

const typeOptions = [
  { value: 'article', label: 'Article' },
  { value: 'guide', label: 'Guide' },
  { value: 'faq', label: 'FAQ' },
  { value: 'tutorial', label: 'Tutorial' },
  { value: 'video', label: 'Video' },
  { value: 'release_notes', label: 'Release Notes' },
];

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

const categoryOptions = computed(() => [
  { value: '', label: 'None' },
  ...flattenCategories(store.categories),
]);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(async () => {
  store.error = null;
  store.successMessage = null;
  await store.fetchCategories().catch(() => {});

  if (!isEdit.value) {
    return;
  }

  try {
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
    tagsInput.value = (article.tags || []).map((tag) => tag.name).join(', ');
  } catch {
    // Toast is shown from store.error.
  }
});

function flattenCategories(items, depth = 0) {
  const result = [];
  (items || []).forEach((item) => {
    result.push({
      value: item.uuid,
      label: depth ? `${'— '.repeat(depth)}${item.name}` : item.name,
    });
    result.push(...flattenCategories(item.children || [], depth + 1));
  });
  return result;
}

async function submit() {
  const payload = {
    ...form,
    category_id: form.category_id || null,
    content_id: form.content_id || null,
    tags: tagsInput.value
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean),
  };

  try {
    const article = isEdit.value
      ? await store.updateArticle(route.params.id, payload)
      : await store.createArticle(payload);

    const uuid = article?.uuid || article?.data?.uuid;
    if (!uuid) {
      toast.success('Article saved.');
      await router.push({ name: 'support.knowledge.index' });
      return;
    }

    try {
      await router.push({ name: 'support.knowledge.show', params: { id: uuid } });
    } catch {
      toast.success('Article saved.');
      await router.push({ name: 'support.knowledge.index' });
    }
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
