<template>
  <div>
    <!-- <PageHeader
      title="Compare policy versions"
      description="Field-level difference viewer for two immutable policy snapshots."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.policies.versions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Version timeline
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'compliance.policies.versions', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Version timeline
        </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="mb-4 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-3">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">From</label>
        <select v-model="fromVersion" class="input">
          <option value="" disabled>Select version</option>
          <option v-for="item in store.versions" :key="item.uuid" :value="String(item.version)">
            v{{ item.version }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">To</label>
        <select v-model="toVersion" class="input">
          <option value="" disabled>Select version</option>
          <option
            v-for="item in store.versions"
            :key="`to-${item.uuid}`"
            :value="String(item.version)"
          >
            v{{ item.version }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="!fromVersion || !toVersion || fromVersion === toVersion || store.loading"
          @click="runCompare"
        >
          Compare
        </button>
      </div>
    </div>

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="store.comparison" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-600">
          Comparing
          <span class="font-semibold text-slate-900">v{{ store.comparison.from?.version }}</span>
          →
          <span class="font-semibold text-slate-900">v{{ store.comparison.to?.version }}</span>
          ·
          {{ (store.comparison.comparison?.changed_fields || []).length }} changed field(s)
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
            <tr
              v-for="(diff, field) in store.comparison.comparison?.changes || {}"
              :key="field"
            >
              <td class="px-4 py-3 align-top font-medium text-slate-800">{{ field }}</td>
              <td class="px-4 py-3 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.from) }}
              </td>
              <td class="px-4 py-3 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.to) }}
              </td>
            </tr>
            <tr v-if="!Object.keys(store.comparison.comparison?.changes || {}).length">
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
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const route = useRoute();
const store = usePolicyStore();
const fromVersion = ref('');
const toVersion = ref('');

onMounted(async () => {
  await store.fetchVersions(route.params.id);
  if (store.versions.length >= 2) {
    fromVersion.value = String(store.versions[1].version);
    toVersion.value = String(store.versions[0].version);
  }
});

async function runCompare() {
  await store.compareVersions(route.params.id, fromVersion.value, toVersion.value);
}

function displayValue(value) {
  if (value == null || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
}
</script>
