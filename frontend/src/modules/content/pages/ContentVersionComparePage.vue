<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'content.versions', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Version timeline
      </RouterLink>
    </Teleport>

    <ContentItemSubnav :content-id="route.params.id" />

    <div
      class="mb-4 grid gap-4 rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-6 md:grid-cols-[1fr_1fr_auto]"
    >
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">
          From
        </label>
        <SelectBox
          v-model="fromId"
          size="lg"
          wrapper-class="w-full"
          placeholder="Select version"
          :options="versionOptions"
        />
      </div>
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">
          To
        </label>
        <SelectBox
          v-model="toId"
          size="lg"
          wrapper-class="w-full"
          placeholder="Select version"
          :options="versionOptions"
        />
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="h-12 w-full rounded-[12px] bg-brand-600 px-6 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 md:w-auto"
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

    <div v-if="contentStore.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else-if="comparison" class="space-y-4">
      <div class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <p class="text-sm text-slate-600">
          Comparing
          <span class="font-semibold text-slate-900">v{{ comparison.from?.version }}</span>
          →
          <span class="font-semibold text-slate-900">v{{ comparison.to?.version }}</span>
          ·
          {{ (comparison.comparison?.changed_fields || []).length }} changed field(s)
        </p>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="overflow-x-auto px-3">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="border-b border-zinc-100">
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Field</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">From</th>
                <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">To</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(diff, field) in comparison.comparison?.changes || {}"
                :key="field"
                class="border-b border-zinc-100 last:border-b-0 align-top"
              >
                <td class="px-5 py-4 font-medium text-slate-800">{{ field }}</td>
                <td class="px-5 py-4 whitespace-pre-wrap text-slate-600">
                  {{ displayValue(diff.from) }}
                </td>
                <td class="px-5 py-4 whitespace-pre-wrap text-slate-600">
                  {{ displayValue(diff.to) }}
                </td>
              </tr>
              <tr v-if="!Object.keys(comparison.comparison?.changes || {}).length">
                <td colspan="3" class="px-5 py-10 text-center text-slate-500">
                  No field-level differences.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-else
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Select two versions to compare</p>
      <p class="mt-1 text-sm text-slate-500">
        Choose a From and To snapshot, then click Compare.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const contentStore = useContentStore();
const fromId = ref('');
const toId = ref('');

const comparison = computed(() => contentStore.comparison);

const versionOptions = computed(() =>
  contentStore.versions.map((item) => ({
    value: item.uuid,
    label: `v${item.version} (${item.status})`,
  })),
);

onMounted(async () => {
  await contentStore.fetchVersions(route.params.id);
  if (contentStore.versions.length >= 2) {
    toId.value = contentStore.versions[0].uuid;
    fromId.value = contentStore.versions[1].uuid;
    await runCompare();
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
