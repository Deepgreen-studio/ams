<template>
  <div>
    <PageHeader
      :title="
        store.versionMeta?.title
          ? `Version timeline · ${store.versionMeta.title}`
          : 'Policy version timeline'
      "
      description="Every update creates an immutable snapshot. Restore always appends a new highest version."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.policies.compare', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Compare versions
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.policies.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to policy
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current version</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">
            v{{ store.versionMeta?.current_version || '—' }}
          </p>
        </div>
        <p class="text-sm text-slate-500">
          {{ store.versions.length }} snapshot{{ store.versions.length === 1 ? '' : 's' }} retained
        </p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.versions.length"
        title="No versions yet"
        description="Snapshots appear after create, update, or restore."
      />
      <ol v-else class="divide-y divide-slate-100">
        <li
          v-for="(item, index) in store.versions"
          :key="item.uuid"
          class="flex gap-4 px-4 py-4 md:px-6"
        >
          <div class="relative flex w-10 shrink-0 flex-col items-center">
            <span
              class="z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
              :class="index === 0 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
            >
              {{ item.version }}
            </span>
            <span
              v-if="index < store.versions.length - 1"
              class="absolute top-8 h-full w-px bg-slate-200"
            />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="font-medium text-slate-900">
                  v{{ item.version }} · {{ item.status_label || item.status }}
                  <span
                    v-if="item.is_restore"
                    class="ml-2 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700"
                  >
                    Restored from v{{ item.restored_from_version }}
                  </span>
                </p>
                <p class="mt-1 text-sm text-slate-600">{{ item.reason || 'No reason recorded' }}</p>
                <p class="mt-1 text-xs text-slate-400">
                  {{ formatDate(item.created_at) }}
                  <span v-if="item.creator?.full_name"> · {{ item.creator.full_name }}</span>
                </p>
              </div>
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                :disabled="store.saving || index === 0"
                @click="restore(item)"
              >
                Restore
              </button>
            </div>
          </div>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const route = useRoute();
const store = usePolicyStore();

onMounted(() => store.fetchVersions(route.params.id));

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function restore(item) {
  const reason = window.prompt('Reason for restore (optional)', `Restore from v${item.version}`);
  if (reason === null) return;
  await store.restoreVersion(route.params.id, item.version, { reason: reason || undefined });
}
</script>
