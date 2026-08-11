<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates.compare', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Compare
      </RouterLink>
      <RouterLink
        :to="{ name: 'notifications.templates.edit', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Edit
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

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

    <div class="mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
      <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current version</p>
      <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
        v{{ store.templateVersionMeta?.current_version || '—' }}
      </p>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>
      <div v-else-if="!store.templateVersions.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No versions yet.
      </div>
      <ol v-else class="divide-y divide-zinc-100">
        <li
          v-for="(item, index) in store.templateVersions"
          :key="item.uuid"
          class="flex gap-4 px-5 py-4 md:px-6"
        >
          <div class="relative flex w-10 shrink-0 flex-col items-center">
            <span
              class="z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
              :class="index === 0 ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-slate-700'"
            >
              {{ item.version }}
            </span>
            <span
              v-if="index < store.templateVersions.length - 1"
              class="absolute top-8 h-full w-px bg-zinc-200"
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
                class="rounded-[12px] border border-zinc-200 px-3.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-50"
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
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const route = useRoute();

onMounted(() => store.fetchTemplateVersions(route.params.id));

async function restore(item) {
  if (!window.confirm(`Restore version ${item.version} as a new draft?`)) return;
  await store.restoreTemplateVersion(route.params.id, item.uuid);
  await store.fetchTemplateVersions(route.params.id);
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
