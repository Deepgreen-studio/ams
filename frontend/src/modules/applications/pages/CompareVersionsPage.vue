<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.versions', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-zinc-50"
        >
          All versions
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create version
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      class="mb-4 grid gap-4 rounded-[12px] bg-white p-5 sm:p-6 ring-1 ring-zinc-100 md:grid-cols-[1fr_1fr_auto]"
    >
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">
          From
        </label>
        <SelectBox
          v-model="fromId"
          size="lg"
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
          placeholder="Select version"
          :options="versionOptions"
        />
      </div>
      <div class="flex items-end">
        <button
          type="button"
          class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-[12px] bg-brand-600 px-6 text-sm font-medium text-white transition hover:bg-brand-700 disabled:opacity-60 md:min-w-[9rem]"
          :disabled="!canCompare"
          @click="runCompare"
        >
          <ArrowsRightLeftIcon class="h-4 w-4" />
          Compare
        </button>
      </div>
    </div>

    <div v-if="versionsStore.loading" class="space-y-4">
      <div class="h-24 animate-pulse rounded-[12px] bg-slate-100" />
      <div class="grid gap-4 lg:grid-cols-2">
        <div class="h-48 animate-pulse rounded-[12px] bg-slate-100" />
        <div class="h-48 animate-pulse rounded-[12px] bg-slate-100" />
      </div>
    </div>

    <div
      v-else-if="!comparison && !versionsStore.versions.length"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No versions to compare"
        description="Create at least two versions before running a comparison."
        class="px-6 py-10"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'applications.versions.create', params: { id: route.params.id } }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create version
          </RouterLink>
        </template>
      </EmptyState>
    </div>

    <div
      v-else-if="!comparison"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="Ready to compare"
        description="Select a From and To version, then click Compare."
        class="px-6 py-10"
      />
    </div>

    <div v-else class="space-y-4">
      <div
        class="flex flex-wrap items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
      >
        <div class="flex flex-wrap items-center gap-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Semver result</p>
          <span
            class="inline-flex rounded-[12px] px-3 py-1 text-xs font-semibold uppercase"
            :class="resultClass"
          >
            {{ comparison.comparison.result }}
          </span>
        </div>
        <div class="flex flex-wrap gap-4 text-sm text-slate-600">
          <span>
            <span class="font-medium text-slate-900">{{ comparison.comparison.semver_diff.major }}</span>
            major
          </span>
          <span>
            <span class="font-medium text-slate-900">{{ comparison.comparison.semver_diff.minor }}</span>
            minor
          </span>
          <span>
            <span class="font-medium text-slate-900">{{ comparison.comparison.semver_diff.patch }}</span>
            patch
          </span>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">From</p>
          <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
            {{ comparison.from.version_number }}
          </p>
          <p class="mt-1 text-sm text-slate-500">
            Build {{ comparison.from.build_number || '—' }} · {{ comparison.from.status }}
          </p>
          <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700">
            {{ comparison.from.release_notes || 'No release notes.' }}
          </p>
        </article>
        <article class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">To</p>
          <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
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

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-6 py-4">
          <h3 class="text-base font-semibold text-slate-900">Field differences</h3>
          <p class="text-xs text-slate-500">
            {{ Object.keys(comparison.comparison.changes || {}).length }} changed
          </p>
        </div>

        <div
          v-if="!Object.keys(comparison.comparison.changes || {}).length"
          class="px-6 py-10 text-center text-sm text-slate-500"
        >
          No field-level differences.
        </div>

        <div v-else class="overflow-x-auto px-3">
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
                v-for="(diff, field) in comparison.comparison.changes"
                :key="field"
                class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
              >
                <td class="px-5 py-4 font-semibold text-slate-900">{{ field }}</td>
                <td class="px-5 py-4 text-slate-600">{{ displayValue(diff.from) }}</td>
                <td class="px-5 py-4 text-slate-600">{{ displayValue(diff.to) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const versionsStore = useVersionsStore();
const toast = useToast();
const fromId = ref('');
const toId = ref('');

const comparison = computed(() => versionsStore.comparison);

const versionOptions = computed(() =>
  (versionsStore.versions || []).map((item) => ({
    value: item.uuid,
    label: `${item.version_number} (${item.status})`,
  })),
);

const canCompare = computed(
  () => Boolean(fromId.value && toId.value && fromId.value !== toId.value && !versionsStore.loading),
);

const resultClass = computed(() => {
  switch (comparison.value?.comparison?.result) {
    case 'upgrade':
      return 'bg-emerald-50 text-emerald-700';
    case 'downgrade':
      return 'bg-rose-50 text-rose-700';
    default:
      return 'bg-zinc-100 text-zinc-700';
  }
});

watch(
  () => versionsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to compare versions');
  },
);

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
