<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'content.compare', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Compare versions
      </RouterLink>
      <RouterLink
        :to="{ name: 'content.edit', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to editor
      </RouterLink>
    </Teleport>

    <ContentItemSubnav :content-id="route.params.id" />

    <div
      v-if="contentStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contentStore.successMessage }}
    </div>
    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div class="mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 sm:px-8 sm:py-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current version</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
            v{{ contentStore.versionMeta?.version || '—' }}
          </p>
        </div>
        <p class="text-sm text-slate-500">
          {{ contentStore.versions.length }} snapshot{{
            contentStore.versions.length === 1 ? '' : 's'
          }}
          retained
        </p>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div v-if="contentStore.loading" class="space-y-3 px-8 py-6">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>
      <EmptyState
        v-else-if="!contentStore.versions.length"
        title="No versions yet"
        description="Snapshots appear after create, save, publish, unpublish, or restore."
        class="px-8 py-6"
      />
      <ol v-else class="divide-y divide-zinc-100">
        <li
          v-for="(item, index) in contentStore.versions"
          :key="item.uuid"
          class="flex gap-4 px-5 py-4 transition hover:bg-zinc-50/60 md:px-6"
        >
          <div class="relative flex w-10 shrink-0 flex-col items-center">
            <span
              class="z-10 grid h-9 w-9 shrink-0 place-items-center rounded-[10px] text-xs font-semibold leading-none"
              :class="index === 0 ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-slate-700'"
            >
              {{ item.version }}
            </span>
            <span
              v-if="index < contentStore.versions.length - 1"
              class="absolute top-9 h-full w-px bg-zinc-200"
            />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-semibold text-slate-900">
                  v{{ item.version }}
                  <span class="font-normal text-slate-400">·</span>
                  {{ statusLabel(item.status) }}
                </p>
                <p class="mt-1 text-sm text-slate-600">
                  {{ item.reason || 'No reason recorded' }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                  {{ formatDate(item.created_at) }}
                  <span v-if="item.creator?.full_name"> · {{ item.creator.full_name }}</span>
                </p>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <button
                  type="button"
                  class="rounded-[10px] px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-100"
                  @click="openViewer(item)"
                >
                  View snapshot
                </button>
                <button
                  type="button"
                  class="rounded-[10px] px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                  :disabled="contentStore.saving || index === 0"
                  @click="restore(item)"
                >
                  Restore
                </button>
              </div>
            </div>
          </div>
        </li>
      </ol>
    </div>

    <div
      v-if="viewerOpen"
      class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center"
      @click.self="viewerOpen = false"
    >
      <div
        class="max-h-[85vh] w-full max-w-3xl overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-zinc-100"
      >
        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">
              Snapshot v{{ viewerVersion?.version }}
            </h3>
            <p class="text-xs text-slate-500">{{ viewerVersion?.reason || 'History viewer' }}</p>
          </div>
          <button
            type="button"
            class="rounded-[10px] px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-zinc-100"
            @click="viewerOpen = false"
          >
            Close
          </button>
        </div>
        <div class="max-h-[70vh] overflow-auto p-5">
          <div v-if="viewerLoading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />
          <dl v-else class="grid gap-4 md:grid-cols-2">
            <div v-for="(value, key) in displaySnapshot" :key="key" class="min-w-0">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ key }}</dt>
              <dd class="mt-1 break-words whitespace-pre-wrap text-sm text-slate-800">
                {{ formatSnapshotValue(value) }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import { contentService } from '@/modules/content/services/contentService';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const contentStore = useContentStore();
const viewerOpen = ref(false);
const viewerLoading = ref(false);
const viewerVersion = ref(null);
const viewerSnapshot = ref(null);

const displaySnapshot = computed(() => {
  const snapshot = viewerSnapshot.value || {};
  const prefer = [
    'title',
    'slug',
    'status_slug',
    'summary',
    'excerpt',
    'body',
    'body_format',
    'seo_title',
    'seo_description',
    'seo_keywords',
    'canonical_url',
    'category_names',
    'tag_names',
    'is_featured',
    'published_at',
  ];
  const ordered = {};
  prefer.forEach((key) => {
    if (Object.prototype.hasOwnProperty.call(snapshot, key)) {
      ordered[key] = snapshot[key];
    }
  });
  Object.keys(snapshot).forEach((key) => {
    if (!Object.prototype.hasOwnProperty.call(ordered, key) && key !== 'editor_json') {
      ordered[key] = snapshot[key];
    }
  });
  if (snapshot.editor_json != null) {
    ordered.editor_json = snapshot.editor_json;
  }
  return ordered;
});

onMounted(() => {
  contentStore.fetchVersions(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusLabel(status) {
  if (!status) return 'Unknown';
  return String(status).charAt(0).toUpperCase() + String(status).slice(1);
}

function formatSnapshotValue(value) {
  if (value == null || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
}

async function openViewer(item) {
  viewerOpen.value = true;
  viewerLoading.value = true;
  viewerVersion.value = item;
  viewerSnapshot.value = null;
  try {
    const { data } = await contentService.version(route.params.id, item.uuid);
    viewerVersion.value = data.data?.version ?? item;
    viewerSnapshot.value = data.data?.version?.snapshot ?? null;
  } catch (err) {
    contentStore.error = err?.message || 'Unable to load snapshot';
    viewerOpen.value = false;
  } finally {
    viewerLoading.value = false;
  }
}

async function restore(item) {
  const confirmed = window.confirm(
    `Restore content to version ${item.version}? A new version will be created from this snapshot.`,
  );
  if (!confirmed) return;
  await contentStore.restoreVersion(route.params.id, item.uuid, {
    reason: `Restored from version ${item.version}`,
  });
}
</script>
