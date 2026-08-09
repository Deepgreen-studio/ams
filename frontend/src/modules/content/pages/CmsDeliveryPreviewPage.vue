<template>
  <div>
    <!-- <PageHeader
      title="CMS Delivery Preview"
      description="Preview published content delivery, SEO packages, and public search results exactly as headless consumers receive them."
    /> -->
    <ContentSubnav />

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-medium"
        :class="
          activeTab === tab.id ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100'
        "
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div
      v-if="error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-if="activeTab === 'content'" class="grid gap-4 xl:grid-cols-5">
      <div class="space-y-4 xl:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 ">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Content UUID or slug</label>
          <input
            v-model="contentId"
            type="text"
            class="mb-3 w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            placeholder="uuid or slug"
          />
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Type (optional for slug)</label
          >
          <input
            v-model="contentType"
            type="text"
            class="mb-4 w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            placeholder="page, blog…"
          />
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
              @click="loadPublicContent"
            >
              Load public
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="loadPrivatePreview"
            >
              Private preview
            </button>
          </div>
        </div>
        <div v-if="seo" class="rounded-xl border border-slate-200 bg-white p-4">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Canonical</p>
          <p class="break-all text-sm text-slate-700">{{ seo.canonical_url }}</p>
          <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Views</p>
          <p class="text-sm text-slate-700">{{ content?.view_count ?? 0 }}</p>
        </div>
      </div>

      <div class="space-y-4 xl:col-span-3">
        <ContentPreview
          v-if="content"
          live
          :title="content.title"
          :slug="content.slug"
          :summary="content.summary"
          :excerpt="content.excerpt"
          :body="content.body"
          :body-format="content.body_format"
          :featured-image="content.featured_image"
          :seo-title="seo?.title"
          :seo-description="seo?.description"
          :keywords="seo?.keywords"
          :canonical-url="seo?.canonical_url"
        />
        <SeoPreviewPanel
          v-if="seo"
          :title="content?.title"
          :seo-title="seo.title"
          :seo-description="seo.description"
          :canonical-url="seo.canonical_url"
          :featured-image="content?.featured_image"
          :og-title="seo.open_graph?.title"
          :og-description="seo.open_graph?.description"
          :og-image="seo.open_graph?.image"
          :twitter-card="seo.twitter_card?.card"
          :twitter-title="seo.twitter_card?.title"
          :twitter-description="seo.twitter_card?.description"
          :twitter-image="seo.twitter_card?.image"
          :schema-json="seo.schema_org"
          :slug="content?.slug"
          :type-slug="content?.type?.slug"
        />
      </div>
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-5">
      <div class="rounded-xl border border-slate-200 bg-white p-5  xl:col-span-2">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Search query</label>
        <div class="flex gap-2">
          <input
            v-model="searchQuery"
            type="search"
            class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            placeholder="Search published content…"
            @keyup.enter="runSearch"
          />
          <button
            type="button"
            class="shrink-0 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            @click="runSearch"
          >
            Search
          </button>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium"
            @click="loadCollection('featured')"
          >
            Featured
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium"
            @click="loadCollection('latest')"
          >
            Latest
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium"
            @click="loadCollection('popular')"
          >
            Popular
          </button>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 xl:col-span-3">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">{{ collectionLabel }}</h2>
          <span class="text-xs text-slate-500">{{ results.length }} result(s)</span>
        </div>
        <div v-if="loading" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded bg-slate-100" />
        </div>
        <div
          v-else-if="results.length === 0"
          class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500"
        >
          No published content matched.
        </div>
        <ul v-else class="divide-y divide-slate-100">
          <li
            v-for="item in results"
            :key="item.uuid"
            class="cursor-pointer py-3 hover:bg-slate-50"
            @click="openResult(item)"
          >
            <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ item.type?.slug }} · /{{ item.slug }} · views {{ item.view_count || 0 }}
            </p>
            <p class="mt-1 line-clamp-2 text-sm text-slate-600">
              {{ item.excerpt || item.summary }}
            </p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ContentPreview from '@/modules/content/components/ContentPreview.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import SeoPreviewPanel from '@/modules/content/components/SeoPreviewPanel.vue';
import { contentService } from '@/modules/content/services/contentService';

const tabs = [
  { id: 'content', label: 'Content Preview' },
  { id: 'search', label: 'Search Preview' },
];

const activeTab = ref('content');
const contentId = ref('');
const contentType = ref('');
const content = ref(null);
const seo = ref(null);
const error = ref('');
const searchQuery = ref('');
const results = ref([]);
const loading = ref(false);
const collectionLabel = ref('Search results');

async function loadPublicContent() {
  error.value = '';
  try {
    const params = contentType.value ? { type: contentType.value } : {};
    const { data } = await contentService.cmsPublicContent(contentId.value, params);
    content.value = data?.data?.content || null;
    seo.value = content.value?.seo || null;
  } catch (err) {
    error.value = err?.message || 'Failed to load public content.';
    content.value = null;
    seo.value = null;
  }
}

async function loadPrivatePreview() {
  error.value = '';
  try {
    const params = contentType.value ? { type: contentType.value } : {};
    const { data } = await contentService.cmsPrivatePreview(contentId.value, params);
    content.value = data?.data?.content || null;
    seo.value = data?.data?.seo || content.value?.seo || null;
  } catch (err) {
    error.value = err?.message || 'Failed to load private preview.';
    content.value = null;
    seo.value = null;
  }
}

async function runSearch() {
  loading.value = true;
  collectionLabel.value = `Search: “${searchQuery.value || ''}”`;
  error.value = '';
  try {
    const { data } = await contentService.cmsPublicSearch({
      q: searchQuery.value,
      include_body: false,
    });
    results.value = data?.data?.contents?.items || [];
  } catch (err) {
    error.value = err?.message || 'Search failed.';
    results.value = [];
  } finally {
    loading.value = false;
  }
}

async function loadCollection(kind) {
  loading.value = true;
  collectionLabel.value = kind.charAt(0).toUpperCase() + kind.slice(1);
  error.value = '';
  try {
    const map = {
      featured: contentService.cmsPublicFeatured,
      latest: contentService.cmsPublicLatest,
      popular: contentService.cmsPublicPopular,
    };
    const { data } = await map[kind]({ include_body: false });
    results.value = data?.data?.contents?.items || [];
  } catch (err) {
    error.value = err?.message || 'Failed to load collection.';
    results.value = [];
  } finally {
    loading.value = false;
  }
}

function openResult(item) {
  contentId.value = item.uuid;
  contentType.value = item.type?.slug || '';
  activeTab.value = 'content';
  loadPublicContent();
}
</script>
