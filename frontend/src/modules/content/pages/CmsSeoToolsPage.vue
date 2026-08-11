<template>
  <div>
    <!-- <PageHeader
      title="SEO Tools"
      description="Inspect sitemap and robots configuration, and validate social metadata for delivered CMS content."
    /> -->
    <ContentSubnav />

    <div class="mb-5 grid gap-3 sm:grid-cols-2">
      <a
        :href="sitemapUrl"
        target="_blank"
        rel="noreferrer"
        class="rounded-[12px] bg-white px-4 py-3.5 text-sm font-medium text-brand-700 ring-1 ring-zinc-100 transition hover:bg-brand-50 hover:ring-brand-200"
      >
        Open /sitemap.xml
      </a>
      <a
        :href="robotsUrl"
        target="_blank"
        rel="noreferrer"
        class="rounded-[12px] bg-white px-4 py-3.5 text-sm font-medium text-brand-700 ring-1 ring-zinc-100 transition hover:bg-brand-50 hover:ring-brand-200"
      >
        Open /robots.txt
      </a>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
      <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Sitemap entries</h2>
          <button
            type="button"
            class="text-xs font-medium text-brand-700 hover:underline"
            @click="loadSitemap"
          >
            Refresh
          </button>
        </div>
        <p class="mb-3 text-xs text-slate-500">{{ sitemapCount }} URL(s)</p>
        <ul class="max-h-96 space-y-2 overflow-auto text-sm">
          <li
            v-for="entry in sitemapEntries"
            :key="entry.loc"
            class="rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
          >
            <p class="break-all font-medium text-slate-800">{{ entry.loc }}</p>
            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span>{{ entry.lastmod || '—' }}</span>
              <span>·</span>
              <span>{{ entry.changefreq }}</span>
              <span
                class="inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700"
              >
                {{ entry.priority }}
              </span>
            </div>
          </li>
        </ul>
      </div>

      <div class="space-y-4">
        <div class="rounded-[12px] bg-slate-950 p-5">
          <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-200">robots.txt</h2>
            <button
              type="button"
              class="text-xs font-medium text-emerald-300 hover:underline"
              @click="loadRobots"
            >
              Refresh
            </button>
          </div>
          <pre class="overflow-auto text-xs leading-relaxed text-emerald-300">{{ robotsText }}</pre>
        </div>

        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">SEO field checker</h2>
          <label class="mb-1.5 block text-sm font-medium text-slate-700"
            >Published content UUID / slug</label
          >
          <div class="mb-3 flex gap-2">
            <input
              v-model="seoId"
              type="text"
              class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="uuid or slug"
            />
            <button
              type="button"
              class="shrink-0 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
              @click="loadSeo"
            >
              Load
            </button>
          </div>
          <p v-if="seoError" class="mb-3 text-sm text-rose-600">{{ seoError }}</p>
          <SeoPreviewPanel
            v-if="seo"
            :seo-title="seo.title"
            :seo-description="seo.description"
            :canonical-url="seo.canonical_url"
            :og-title="seo.open_graph?.title"
            :og-description="seo.open_graph?.description"
            :og-image="seo.open_graph?.image"
            :twitter-card="seo.twitter_card?.card"
            :twitter-title="seo.twitter_card?.title"
            :twitter-description="seo.twitter_card?.description"
            :twitter-image="seo.twitter_card?.image"
            :schema-json="seo.schema_org"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import SeoPreviewPanel from '@/modules/content/components/SeoPreviewPanel.vue';
import { contentService } from '@/modules/content/services/contentService';

/** Public crawler endpoints live on the backend root (not under /api/v1). */
function publicBackendUrl(path) {
  const apiBase = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '');

  if (!apiBase) {
    return path;
  }

  try {
    const parsed = new URL(apiBase, typeof window !== 'undefined' ? window.location.origin : apiBase);
    return `${parsed.origin}${path}`;
  } catch {
    return path;
  }
}

const sitemapUrl = computed(() => publicBackendUrl('/sitemap.xml'));
const robotsUrl = computed(() => publicBackendUrl('/robots.txt'));

const sitemapEntries = ref([]);
const sitemapCount = ref(0);
const robotsText = ref('Loading…');
const seoId = ref('');
const seo = ref(null);
const seoError = ref('');

onMounted(async () => {
  await Promise.all([loadSitemap(), loadRobots()]);
});

async function loadSitemap() {
  const { data } = await contentService.cmsSitemapJson();
  sitemapEntries.value = data?.data?.entries || [];
  sitemapCount.value = data?.data?.count || sitemapEntries.value.length;
}

async function loadRobots() {
  const { data } = await contentService.cmsRobotsJson();
  robotsText.value = data?.data?.robots || '';
}

async function loadSeo() {
  seoError.value = '';
  try {
    const { data } = await contentService.cmsPublicSeo(seoId.value);
    seo.value = data?.data?.seo || null;
  } catch (error) {
    seo.value = null;
    seoError.value = error?.message || 'Unable to load SEO package.';
  }
}
</script>
