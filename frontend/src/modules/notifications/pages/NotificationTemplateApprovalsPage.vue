<template>
  <div>
    <!-- <PageHeader title="Template Approvals" description="Review submitted notification templates before publish.">
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.templates' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Template manager
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'notifications.templates' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Template manager
        </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <p v-if="store.successMessage" class="mb-4 text-sm text-emerald-700">{{ store.successMessage }}</p>
    <p v-if="store.error" class="mb-4 text-sm text-rose-600">{{ store.error }}</p>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Template</th>
            <th class="px-4 py-3">Version</th>
            <th class="px-4 py-3">Requested by</th>
            <th class="px-4 py-3">Comments</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="!store.templateApprovals.length">
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No pending approvals.</td>
          </tr>
          <tr v-for="item in store.templateApprovals" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.template?.name }}</p>
              <p class="text-xs text-slate-500">{{ item.template?.channel }} · {{ item.template?.locale }}</p>
            </td>
            <td class="px-4 py-3">v{{ item.version?.version }}</td>
            <td class="px-4 py-3">{{ item.requester?.full_name || '—' }}</td>
            <td class="px-4 py-3">{{ item.comments || '—' }}</td>
            <td class="px-4 py-3 text-right space-x-2">
              <button type="button" class="text-emerald-700 hover:underline" @click="approve(item)">Approve</button>
              <button type="button" class="text-rose-600 hover:underline" @click="reject(item)">Reject</button>
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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
