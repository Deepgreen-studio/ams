<template>
  <div>
    <!-- <PageHeader
      :title="store.templateVersionMeta?.name ? `Versions · ${store.templateVersionMeta.name}` : 'Template Version History'"
      description="Every save creates an immutable snapshot. Restore always appends a new draft version."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.templates.compare', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Compare
        </RouterLink>
        <RouterLink
          :to="{ name: 'notifications.templates.edit', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'notifications.templates.compare', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Compare
        </RouterLink>
        <RouterLink
          :to="{ name: 'notifications.templates.edit', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <p v-if="store.successMessage" class="mb-4 text-sm text-emerald-700">{{ store.successMessage }}</p>
    <p v-if="store.error" class="mb-4 text-sm text-rose-600">{{ store.error }}</p>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
      <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current version</p>
      <p class="mt-1 text-2xl font-semibold text-slate-900">v{{ store.templateVersionMeta?.current_version || '—' }}</p>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <div v-else-if="!store.templateVersions.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No versions yet.
      </div>
      <ol v-else class="divide-y divide-slate-100">
        <li v-for="(item, index) in store.templateVersions" :key="item.uuid" class="flex gap-4 px-4 py-4 md:px-6">
          <div class="relative flex w-10 shrink-0 flex-col items-center">
            <span
              class="z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
              :class="index === 0 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
            >
              {{ item.version }}
            </span>
            <span v-if="index < store.templateVersions.length - 1" class="absolute top-8 h-full w-px bg-slate-200" />
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
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
