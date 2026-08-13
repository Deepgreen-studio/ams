<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.reports' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <DocumentChartBarIcon class="h-4 w-4" />
        All reports
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.reports' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        New report
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <div v-if="store.loading && !store.reports.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-36 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <EmptyState
      v-else-if="!store.reports.length"
      title="No saved reports yet"
      description="Mark a report as saved in the designer to reuse it from this library."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'analytics.reports' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          New report
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <RouterLink
        v-for="item in store.reports"
        :key="item.uuid"
        :to="{ name: 'analytics.reports.designer', params: { uuid: item.uuid } }"
        class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <p class="font-medium text-slate-900">{{ item.name }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ item.description || item.slug }}</p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
          <span class="rounded-md bg-zinc-100 px-2 py-1 capitalize text-slate-700">{{ item.report_type }}</span>
          <span v-if="item.is_scheduled" class="rounded-md bg-amber-50 px-2 py-1 text-amber-700">Scheduled</span>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { DocumentChartBarIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import EmptyState from '@/components/ui/EmptyState.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const toast = useToast();

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(async () => {
  store.successMessage = null;
  store.error = null;
  await store.fetchReports({ is_saved: 1, page: 1 });
});
</script>
