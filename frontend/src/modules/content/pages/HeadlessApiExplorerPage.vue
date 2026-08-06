<template>
  <div>
    <PageHeader
      title="CMS API Explorer"
      description="Try public and private headless CMS endpoints, manage delivery API keys, and inspect live JSON responses."
    />
    <ContentSubnav />

    <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-lg px-4 py-2 text-sm font-medium transition"
        :class="
          activeTab === tab.id
            ? 'bg-brand-50 text-brand-700 shadow-sm'
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
        "
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="activeTab === 'explorer'" class="grid items-stretch gap-5 xl:grid-cols-12">
      <div class="flex flex-col gap-4 xl:col-span-5">
        <section class="flex-1 rounded-xl border border-slate-200 bg-white shadow-sm">
          <header class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Request</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Choose a scope and endpoint, then send a live CMS request.
            </p>
          </header>

          <div class="space-y-4 p-5">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="api-scope"
                  >Scope</label
                >
                <select
                  id="api-scope"
                  v-model="form.scope"
                  class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                >
                  <option value="public">Public API</option>
                  <option value="private">Private API</option>
                  <option value="seo">SEO helpers</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="api-endpoint"
                  >Endpoint</label
                >
                <select
                  id="api-endpoint"
                  v-model="form.endpoint"
                  class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 font-mono text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                >
                  <option v-for="item in endpointOptions" :key="item.path" :value="item.path">
                    {{ item.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700" for="api-extra"
                >Path / query extras</label
              >
              <input
                id="api-extra"
                v-model="form.pathExtra"
                type="text"
                class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 font-mono text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                placeholder=":uuid, ?q=hello, ?type=page"
              />
              <p class="mt-1.5 text-xs text-slate-500">
                Replace <code class="rounded bg-slate-100 px-1">{id}</code> /
                <code class="rounded bg-slate-100 px-1">{slug}</code> or append query strings.
              </p>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700" for="api-key"
                >CMS API key</label
              >
              <input
                id="api-key"
                v-model="form.apiKey"
                type="text"
                class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 font-mono text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                placeholder="cms_… (optional for public)"
              />
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                Resolved URL
              </p>
              <p class="mt-1.5 break-all font-mono text-xs text-slate-800">{{ resolvedUrl }}</p>
            </div>

            <button
              type="button"
              class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="loading"
              @click="runRequest"
            >
              {{ loading ? 'Running…' : 'Send request' }}
            </button>
          </div>
        </section>
      </div>

      <section
        class="flex min-h-[28rem] flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-950 shadow-sm xl:col-span-7"
      >
        <header
          class="flex items-center justify-between border-b border-slate-800 px-5 py-3.5"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Response</p>
          <span
            v-if="statusCode"
            class="rounded-md px-2 py-0.5 text-xs font-medium"
            :class="
              statusCode >= 200 && statusCode < 300
                ? 'bg-emerald-500/15 text-emerald-300'
                : 'bg-rose-500/15 text-rose-300'
            "
          >
            HTTP {{ statusCode }}
          </span>
          <span v-else class="text-xs text-slate-500">Awaiting request</span>
        </header>
        <pre
          class="flex-1 overflow-auto px-5 py-4 text-xs leading-relaxed text-emerald-300"
          >{{ responseText }}</pre
        >
      </section>
    </div>

    <div v-else class="grid gap-5 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4">
          <h2 class="text-sm font-semibold text-slate-900">Create CMS API key</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Keys authenticate private headless delivery without a dashboard session.
          </p>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="key-name"
              >Name</label
            >
            <input
              id="key-name"
              v-model="keyForm.name"
              type="text"
              class="w-full h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
              placeholder="Mobile app production"
            />
          </div>
          <button
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="keyLoading"
            @click="createKey"
          >
            {{ keyLoading ? 'Generating…' : 'Generate key' }}
          </button>
          <div
            v-if="plainTextKey"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-900"
          >
            <p class="font-medium">Copy now — shown once</p>
            <p class="mt-1 break-all font-mono text-xs">{{ plainTextKey }}</p>
          </div>
          <p v-if="keyError" class="text-sm text-rose-600">{{ keyError }}</p>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Existing keys</h2>
            <p class="mt-0.5 text-xs text-slate-500">Active and revoked delivery keys.</p>
          </div>
          <button
            type="button"
            class="text-xs font-medium text-brand-700 hover:underline"
            @click="loadKeys"
          >
            Refresh
          </button>
        </header>
        <div class="p-5">
          <div
            v-if="keys.length === 0"
            class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500"
          >
            No API keys yet.
          </div>
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="key in keys"
              :key="key.uuid"
              class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
            >
              <div>
                <p class="text-sm font-medium text-slate-900">{{ key.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ key.key_prefix }}… · {{ key.is_active ? 'Active' : 'Revoked' }}
                </p>
              </div>
              <button
                type="button"
                class="text-xs font-medium text-rose-600 hover:underline"
                @click="revokeKey(key.uuid)"
              >
                Revoke
              </button>
            </li>
          </ul>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { contentService } from '@/modules/content/services/contentService';

const tabs = [
  { id: 'explorer', label: 'API Explorer' },
  { id: 'keys', label: 'API Keys' },
];

const activeTab = ref('explorer');
const loading = ref(false);
const statusCode = ref(null);
const responseText = ref('Send a request to inspect the JSON response.');
const keys = ref([]);
const keyLoading = ref(false);
const keyError = ref('');
const plainTextKey = ref('');

const form = reactive({
  scope: 'public',
  endpoint: '/contents',
  pathExtra: '',
  apiKey: '',
});

const keyForm = reactive({
  name: '',
});

const catalog = {
  public: [
    { path: '/contents', label: 'GET /contents' },
    { path: '/contents/{id}', label: 'GET /contents/{id}' },
    { path: '/contents/{id}/seo', label: 'GET /contents/{id}/seo' },
    { path: '/search', label: 'GET /search?q=' },
    { path: '/featured', label: 'GET /featured' },
    { path: '/latest', label: 'GET /latest' },
    { path: '/popular', label: 'GET /popular' },
    { path: '/categories', label: 'GET /categories' },
    { path: '/categories/{slug}/contents', label: 'GET /categories/{slug}/contents' },
    { path: '/tags', label: 'GET /tags' },
    { path: '/tags/{slug}/contents', label: 'GET /tags/{slug}/contents' },
  ],
  private: [
    { path: '/contents', label: 'GET /contents' },
    { path: '/contents/{id}', label: 'GET /contents/{id}' },
    { path: '/contents/{id}/preview', label: 'GET /contents/{id}/preview' },
    { path: '/search', label: 'GET /search?q=' },
    { path: '/featured', label: 'GET /featured' },
    { path: '/latest', label: 'GET /latest' },
    { path: '/popular', label: 'GET /popular' },
    { path: '/categories', label: 'GET /categories' },
    { path: '/tags', label: 'GET /tags' },
  ],
  seo: [
    { path: '/sitemap.json', label: 'GET /sitemap.json' },
    { path: '/robots.json', label: 'GET /robots.json' },
  ],
};

const endpointOptions = computed(() => catalog[form.scope] || []);

const resolvedUrl = computed(() => {
  const base =
    form.scope === 'seo' ? '/cms/seo' : form.scope === 'private' ? '/cms/private' : '/cms/public';
  let path = form.endpoint;
  const extra = form.pathExtra.trim();
  if (path.includes('{id}') || path.includes('{slug}')) {
    const id = extra.split(/[?&]/)[0] || ':id';
    path = path.replace('{id}', id).replace('{slug}', id);
    const queryIndex = extra.indexOf('?');
    if (queryIndex >= 0) {
      path += extra.slice(queryIndex);
    }
  } else if (extra) {
    path += extra.startsWith('?') || extra.startsWith('/') ? extra : `/${extra}`;
  }
  return `${base}${path}`;
});

watch(
  () => form.scope,
  () => {
    form.endpoint = endpointOptions.value[0]?.path || '/contents';
  },
);

onMounted(() => {
  loadKeys();
});

async function runRequest() {
  loading.value = true;
  statusCode.value = null;
  try {
    const { data, status } = await contentService.cmsRequest(resolvedUrl.value, {
      apiKey: form.apiKey || undefined,
    });
    statusCode.value = status;
    responseText.value = JSON.stringify(data, null, 2);
  } catch (error) {
    statusCode.value = error?.status || null;
    responseText.value = JSON.stringify(error, null, 2);
  } finally {
    loading.value = false;
  }
}

async function loadKeys() {
  try {
    const { data } = await contentService.listCmsApiKeys();
    keys.value = data?.data?.api_keys?.items || [];
  } catch {
    keys.value = [];
  }
}

async function createKey() {
  keyLoading.value = true;
  keyError.value = '';
  plainTextKey.value = '';
  try {
    const { data } = await contentService.createCmsApiKey({ name: keyForm.name || 'CMS API Key' });
    plainTextKey.value = data?.data?.plain_text || '';
    form.apiKey = plainTextKey.value;
    keyForm.name = '';
    await loadKeys();
  } catch (error) {
    keyError.value = error?.message || 'Failed to create API key.';
  } finally {
    keyLoading.value = false;
  }
}

async function revokeKey(uuid) {
  await contentService.revokeCmsApiKey(uuid);
  await loadKeys();
}
</script>
