<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{
          name: 'applications.configurations.edit',
          params: { id: route.params.id, configurationId: route.params.configurationId },
        }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to editor
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div v-if="configurationsStore.loading" class="space-y-3 px-8 py-6">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
      </div>

      <EmptyState
        v-else-if="!configurationsStore.history.length"
        title="No history"
        description="Configuration changes will appear here after updates."
        class="px-8 py-6"
      />

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Version</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Summary</th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
                Status
              </th>
              <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
                Changed
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in configurationsStore.history"
              :key="item.uuid"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4 font-semibold text-slate-900">v{{ item.version }}</td>
              <td class="px-5 py-4 text-slate-600">{{ item.change_summary || '—' }}</td>
              <td class="hidden px-5 py-4 md:table-cell">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                  :class="statusClasses(item.status)"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(item.status)" />
                  {{ statusLabel(item.status) }}
                </span>
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">
                {{ formatDate(item.created_at) }}
                <span class="mt-0.5 block text-xs text-slate-400">{{
                  item.creator?.full_name || '—'
                }}</span>
              </td>
              <td class="px-5 py-4 text-right">
                <button
                  type="button"
                  class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50 disabled:opacity-50"
                  :disabled="configurationsStore.saving"
                  @click="openRestore(item)"
                >
                  Restore
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingRestore)"
      title="Restore configuration"
      :message="`Restore version v${pendingRestore?.version || ''}? The current configuration will be replaced with this snapshot.`"
      confirm-label="Restore"
      :danger="false"
      :loading="configurationsStore.saving"
      @cancel="pendingRestore = null"
      @confirm="confirmRestore"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const configurationsStore = useConfigurationsStore();
const toast = useToast();
const pendingRestore = ref(null);

watch(
  () => configurationsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load history');
  },
);

watch(
  () => configurationsStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(async () => {
  try {
    await configurationsStore.fetchHistory(route.params.id, route.params.configurationId);
  } catch {
    // Toast handled by watcher.
  }
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function statusLabel(status) {
  return String(status || 'draft')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusClasses(status) {
  switch (status) {
    case 'published':
      return 'border-emerald-600 text-emerald-700';
    case 'archived':
      return 'border-slate-400 text-slate-600';
    default:
      return 'border-amber-500 text-amber-700';
  }
}

function statusDot(status) {
  switch (status) {
    case 'published':
      return 'bg-emerald-600';
    case 'archived':
      return 'bg-slate-400';
    default:
      return 'bg-amber-500';
  }
}

function openRestore(item) {
  pendingRestore.value = item;
}

async function confirmRestore() {
  if (!pendingRestore.value) return;
  try {
    await configurationsStore.restoreHistory(
      route.params.id,
      route.params.configurationId,
      pendingRestore.value.uuid,
    );
    pendingRestore.value = null;
  } catch {
    // Toast handled by watcher.
  }
}
</script>
