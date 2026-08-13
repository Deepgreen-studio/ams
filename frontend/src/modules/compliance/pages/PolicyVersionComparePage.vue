<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.versions', params: { id: route.params.id } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        Version timeline
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <h2 class="text-base font-semibold text-slate-900">Compare versions</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Field-level difference viewer for two immutable policy snapshots.
        </p>
      </div>
      <form class="grid gap-4 px-6 py-5 sm:grid-cols-3 sm:px-8" @submit.prevent="runCompare">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">From</label>
          <SelectBox
            v-model="fromVersion"
            size="lg"
            placeholder="Select version"
            :options="fromOptions"
            :disabled="store.loading"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">To</label>
          <SelectBox
            v-model="toVersion"
            size="lg"
            placeholder="Select version"
            :options="toOptions"
            :disabled="store.loading"
          />
        </div>
        <div class="flex items-end">
          <button
            type="submit"
            class="inline-flex h-11 w-full items-center justify-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="!canCompare || store.loading"
          >
            Compare
          </button>
        </div>
      </form>
    </div>

    <div v-if="store.loading && !store.comparison" class="mt-4 h-64 animate-pulse rounded-[12px] bg-zinc-100" />

    <div
      v-else-if="store.comparison"
      class="mt-4 overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <p class="text-sm text-slate-600">
          Comparing
          <span class="font-semibold text-slate-900">v{{ store.comparison.from?.version }}</span>
          →
          <span class="font-semibold text-slate-900">v{{ store.comparison.to?.version }}</span>
          ·
          {{ (store.comparison.comparison?.changed_fields || []).length }} changed field(s)
        </p>
      </div>
      <div class="scrollbar-light overflow-x-auto px-3">
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
              v-for="(diff, field) in store.comparison.comparison?.changes || {}"
              :key="field"
              class="border-b border-zinc-50 last:border-0"
            >
              <td class="px-5 py-4 align-top font-medium text-slate-800">{{ field }}</td>
              <td class="px-5 py-4 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.from) }}
              </td>
              <td class="px-5 py-4 align-top whitespace-pre-wrap text-slate-600">
                {{ displayValue(diff.to) }}
              </td>
            </tr>
            <tr v-if="!Object.keys(store.comparison.comparison?.changes || {}).length">
              <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">
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
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ClockIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const route = useRoute();
const store = usePolicyStore();
const toast = useToast();
const fromVersion = ref('');
const toVersion = ref('');

const versionOptions = computed(() =>
  store.versions.map((item) => ({
    value: String(item.version),
    label: `v${item.version} (${item.status_label || item.status})`,
  })),
);

const fromOptions = computed(() => versionOptions.value);
const toOptions = computed(() => versionOptions.value);

const canCompare = computed(
  () => Boolean(fromVersion.value && toVersion.value && fromVersion.value !== toVersion.value),
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
  store.successMessage = null;
  store.error = null;
  try {
    await store.fetchVersions(route.params.id);
    if (store.versions.length >= 2) {
      fromVersion.value = String(store.versions[1].version);
      toVersion.value = String(store.versions[0].version);
    } else if (store.versions.length === 1) {
      toVersion.value = String(store.versions[0].version);
    }
  } catch {
    // Toast is shown from store.error.
  }
});

async function runCompare() {
  if (!canCompare.value) {
    return;
  }
  try {
    await store.compareVersions(route.params.id, fromVersion.value, toVersion.value);
  } catch {
    // Toast is shown from store.error.
  }
}

function displayValue(value) {
  if (value == null || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
}
</script>
