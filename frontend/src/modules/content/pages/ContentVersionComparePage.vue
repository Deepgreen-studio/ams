<template>
  <div>
    <PageHeader title="Compare versions" description="Difference viewer for two content snapshots.">
      <template #actions>
        <RouterLink
          :to="{ name: 'content.versions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Version timeline
        </RouterLink>
      </template>
    </PageHeader>

    <ContentItemSubnav :content-id="route.params.id" />

    <div class="mb-4 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-3">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >From</label
        >
        <select
          v-model="fromId"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="" disabled>Select version</option>
          <option v-for="item in contentStore.versions" :key="item.uuid" :value="item.uuid">
            v{{ item.version }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >To</label
        >
        <select v-model="toId" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm">
          <option value="" disabled>Select version</option>
          <option v-for="item in contentStore.versions" :key="`to-${item.uuid}`" :value="item.uuid">
            v{{ item.version }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="!fromId || !toId || fromId === toId || contentStore.loading"
          @click="runCompare"
        >
          Compare
        </button>
      </div>
    </div>

    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div v-if="contentStore.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="comparison" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-600">
          Comparing
          <span class="font-semibold text-slate-900">v{{ comparison.from?.version }}</span>
          →
          <span class="font-semibold text-slate-900">v{{ comparison.to?.version }}</span>
          ·
          {{ (comparison.comparison?.changed_fields || []).length }} changed field(s)
        </p>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Field</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">From</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">To</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(diff, field) in comparison.comparison?.changes || {}" :key="field">
              <td class="px-4 py-3 align-top font-medium text-slate-800">{{ field }}</td>
              <td class="px-4 py-3 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.from) }}
              </td>
              <td class="px-4 py-3 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.to) }}
              </td>
            </tr>
            <tr v-if="!Object.keys(comparison.comparison?.changes || {}).length">
              <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                No field-level differences.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const contentStore = useContentStore();
const fromId = ref('');
const toId = ref('');

const comparison = computed(() => contentStore.comparison);

onMounted(async () => {
  await contentStore.fetchVersions(route.params.id);
  if (contentStore.versions.length >= 2) {
    toId.value = contentStore.versions[0].uuid;
    fromId.value = contentStore.versions[1].uuid;
  } else if (contentStore.versions.length === 1) {
    fromId.value = contentStore.versions[0].uuid;
  }
});

async function runCompare() {
  await contentStore.compareVersions(route.params.id, fromId.value, toId.value);
}

function displayValue(value) {
  if (value == null || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
}
</script>
