<template>
  <div>
    <PageHeader
      title="Compare versions"
      description="Diff two semantic versions for this application."
    />
    <ApplicationSubnav :application-id="route.params.id" />

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
          <option v-for="item in versionsStore.versions" :key="item.uuid" :value="item.uuid">
            {{ item.version_number }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >To</label
        >
        <select v-model="toId" class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm">
          <option value="" disabled>Select version</option>
          <option v-for="item in versionsStore.versions" :key="item.uuid" :value="item.uuid">
            {{ item.version_number }} ({{ item.status }})
          </option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="!fromId || !toId || fromId === toId || versionsStore.loading"
          @click="runCompare"
        >
          Compare
        </button>
      </div>
    </div>

    <div
      v-if="versionsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ versionsStore.error }}
    </div>

    <div v-if="versionsStore.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="comparison" class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-center gap-3">
          <span class="text-sm text-slate-600">Semver result:</span>
          <span
            class="rounded-md px-2 py-1 text-xs font-semibold uppercase ring-1 ring-inset"
            :class="resultClass"
          >
            {{ comparison.comparison.result }}
          </span>
          <span class="text-sm text-slate-500">
            Δ major {{ comparison.comparison.semver_diff.major }}, minor
            {{ comparison.comparison.semver_diff.minor }}, patch
            {{ comparison.comparison.semver_diff.patch }}
          </span>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">From</h3>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ comparison.from.version_number }}
          </p>
          <p class="mt-1 text-sm text-slate-500">
            Build {{ comparison.from.build_number || '—' }} · {{ comparison.from.status }}
          </p>
          <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">
            {{ comparison.from.release_notes || 'No release notes.' }}
          </p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">To</h3>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ comparison.to.version_number }}
          </p>
          <p class="mt-1 text-sm text-slate-500">
            Build {{ comparison.to.build_number || '—' }} · {{ comparison.to.status }}
          </p>
          <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">
            {{ comparison.to.release_notes || 'No release notes.' }}
          </p>
        </article>
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
            <tr v-for="(diff, field) in comparison.comparison.changes" :key="field">
              <td class="px-4 py-3 font-medium text-slate-800">{{ field }}</td>
              <td class="px-4 py-3 text-slate-600">{{ displayValue(diff.from) }}</td>
              <td class="px-4 py-3 text-slate-600">{{ displayValue(diff.to) }}</td>
            </tr>
            <tr v-if="!Object.keys(comparison.comparison.changes || {}).length">
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
import { useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const versionsStore = useVersionsStore();
const fromId = ref('');
const toId = ref('');

const comparison = computed(() => versionsStore.comparison);
const resultClass = computed(() => {
  switch (comparison.value?.comparison?.result) {
    case 'upgrade':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'downgrade':
      return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default:
      return 'bg-slate-50 text-slate-700 ring-slate-500/20';
  }
});

onMounted(async () => {
  await versionsStore.fetchVersions(route.params.id, {
    per_page: 100,
    sort_by: 'semver',
    sort_dir: 'desc',
  });
  if (versionsStore.versions.length >= 2) {
    toId.value = versionsStore.versions[0].uuid;
    fromId.value = versionsStore.versions[1].uuid;
  } else if (versionsStore.versions.length === 1) {
    fromId.value = versionsStore.versions[0].uuid;
  }
});

async function runCompare() {
  await versionsStore.compareVersions(route.params.id, fromId.value, toId.value);
}

function displayValue(value) {
  if (value == null || value === '') return '—';
  return String(value);
}
</script>
