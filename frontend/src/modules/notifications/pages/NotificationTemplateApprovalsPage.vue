<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Template manager
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

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Template</th>
            <th class="px-5 py-3.5">Version</th>
            <th class="px-5 py-3.5">Requested by</th>
            <th class="px-5 py-3.5">Comments</th>
            <th class="px-5 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-if="!store.templateApprovals.length">
            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No pending approvals.</td>
          </tr>
          <tr v-for="item in store.templateApprovals" :key="item.uuid" class="hover:bg-zinc-50/80">
            <td class="px-5 py-4">
              <p class="font-medium text-slate-900">{{ item.template?.name }}</p>
              <p class="text-xs text-slate-500">{{ item.template?.channel }} · {{ item.template?.locale }}</p>
            </td>
            <td class="px-5 py-4 text-slate-600">v{{ item.version?.version }}</td>
            <td class="px-5 py-4 text-slate-600">{{ item.requester?.full_name || '—' }}</td>
            <td class="px-5 py-4 text-slate-600">{{ item.comments || '—' }}</td>
            <td class="space-x-3 px-5 py-4 text-right">
              <button type="button" class="font-medium text-emerald-700 hover:underline" @click="approve(item)">
                Approve
              </button>
              <button type="button" class="font-medium text-rose-600 hover:underline" @click="reject(item)">
                Reject
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();

onMounted(() => store.fetchTemplateApprovals({ status: 'pending' }));

async function approve(item) {
  const comments = window.prompt('Approval comments (optional)') || undefined;
  await store.approveTemplate(item.uuid, { comments });
  await store.fetchTemplateApprovals({ status: 'pending' });
}

async function reject(item) {
  const comments = window.prompt('Rejection reason');
  if (comments === null) return;
  await store.rejectTemplate(item.uuid, { comments });
  await store.fetchTemplateApprovals({ status: 'pending' });
}
</script>
