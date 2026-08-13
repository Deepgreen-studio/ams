<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.policies.compare', params: { id: route.params.id } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ArrowsRightLeftIcon class="h-4 w-4" />
        Compare versions
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.policies.show', params: { id: route.params.id } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <DocumentTextIcon class="h-4 w-4" />
        Back to policy
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !store.versions.length" class="space-y-4">
      <div class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.versions.length"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load version timeline</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading snapshots again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <div
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current version</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              v{{ store.versionMeta?.current_version || '—' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
              {{ store.versionMeta?.title || 'Active snapshot' }}
            </p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-brand-50">
            <DocumentTextIcon class="h-5 w-5 text-brand-500" />
          </div>
        </div>
        <div
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Snapshots</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ store.versions.length }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Immutable history retained</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-zinc-100">
            <ClockIcon class="h-5 w-5 text-slate-500" />
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
          <h2 class="text-base font-semibold text-slate-900">Version timeline</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Every update creates an immutable snapshot. Restore always appends a new highest version.
          </p>
        </div>

        <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
          <div v-for="n in 5" :key="n" class="h-16 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <div v-else-if="!store.versions.length" class="px-6 py-16 text-center sm:px-8">
          <p class="text-sm font-medium text-slate-900">No versions yet</p>
          <p class="mt-1 text-xs text-slate-500">Snapshots appear after create, update, or restore.</p>
        </div>
        <ol v-else class="divide-y divide-zinc-100">
          <li
            v-for="(item, index) in store.versions"
            :key="item.uuid"
            class="flex gap-4 px-6 py-5 sm:px-8"
          >
            <div class="relative flex w-10 shrink-0 flex-col items-center">
              <span
                class="z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                :class="index === 0 ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-slate-700'"
              >
                {{ item.version }}
              </span>
              <span
                v-if="index < store.versions.length - 1"
                class="absolute top-8 h-full w-px bg-zinc-200"
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-medium text-slate-900">
                    v{{ item.version }} · {{ item.status_label || item.status }}
                    <span
                      v-if="item.is_restore"
                      class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
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
                  v-if="can('compliance.update')"
                  type="button"
                  class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                  :disabled="store.saving || index === 0"
                  @click="openRestore(item)"
                >
                  Restore
                </button>
              </div>
            </div>
          </li>
        </ol>
      </div>
    </template>

    <DeleteConfirmation
      :open="Boolean(pendingRestore)"
      title="Restore this version?"
      :message="restoreMessage"
      confirm-label="Restore"
      :danger="false"
      :loading="store.saving"
      @cancel="pendingRestore = null"
      @confirm="confirmRestore"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ArrowsRightLeftIcon, ClockIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';

const route = useRoute();
const store = usePolicyStore();
const toast = useToast();
const { can } = usePermissions();
const pendingRestore = ref(null);

const restoreMessage = computed(() => {
  const version = pendingRestore.value?.version;
  if (!version) {
    return 'Restore will append a new highest version from this snapshot.';
  }
  return `Restore from v${version}? A new highest version will be appended. Previous snapshots stay intact.`;
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

async function reload() {
  try {
    await store.fetchVersions(route.params.id);
  } catch {
    // Toast is shown from store.error.
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function openRestore(item) {
  pendingRestore.value = item;
}

async function confirmRestore() {
  if (!pendingRestore.value) {
    return;
  }

  try {
    await store.restoreVersion(route.params.id, pendingRestore.value.version, {
      reason: `Restore from v${pendingRestore.value.version}`,
    });
    toast.success(store.successMessage || 'Version restored successfully.');
    store.successMessage = null;
    pendingRestore.value = null;
  } catch {
    pendingRestore.value = null;
  }
}
</script>
