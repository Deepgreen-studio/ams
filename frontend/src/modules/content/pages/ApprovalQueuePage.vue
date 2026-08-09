<template>
  <div>
    <!-- <PageHeader
      title="Approval queue"
      description="Content awaiting your review, approval, or publish action."
    /> -->
    <ContentSubnav />

    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Pending review</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ contentStore.statistics?.pending_review || 0 }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Reviewed</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ contentStore.statistics?.reviewed || 0 }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Approved</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ contentStore.statistics?.approved || 0 }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Rejected</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ contentStore.statistics?.rejected || 0 }}
        </p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="contentStore.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!contentStore.queue.length"
        title="Queue is clear"
        description="No content is waiting for your workflow level right now."
      />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Title</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
                Type
              </th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
                Updated
              </th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in contentStore.queue" :key="item.uuid">
              <td class="px-4 py-3 font-medium text-slate-900">{{ item.title }}</td>
              <td class="px-4 py-3">
                <StatusBadge :status="item.status?.slug" :label="item.status?.name" />
              </td>
              <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
                {{ item.type?.name || '—' }}
              </td>
              <td class="hidden px-4 py-3 text-slate-500 lg:table-cell">
                {{ formatDate(item.updated_at) }}
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="{ name: 'content.review', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  Open review
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import { useContentStore } from '@/modules/content/stores/content';

const contentStore = useContentStore();

onMounted(() => {
  contentStore.fetchWorkflowQueue({ per_page: 20 });
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
