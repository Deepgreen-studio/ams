<template>
  <div>
    <!-- <PageHeader title="Saved Reports" description="Reusable report definitions marked as saved." /> -->
    <AnalyticsSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-if="store.loading" class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        Loading saved reports…
      </div>
      <div v-else-if="!store.reports.length" class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No saved reports yet.
      </div>
      <RouterLink
        v-for="item in store.reports"
        :key="item.uuid"
        :to="{ name: 'analytics.reports.designer', params: { uuid: item.uuid } }"
        class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-sm"
      >
        <p class="font-medium text-slate-900">{{ item.name }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ item.description || item.slug }}</p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs">
          <span class="rounded-md bg-slate-100 px-2 py-1 capitalize text-slate-700">{{ item.report_type }}</span>
          <span v-if="item.is_scheduled" class="rounded-md bg-amber-50 px-2 py-1 text-amber-700">Scheduled</span>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();

onMounted(async () => {
  await store.fetchReports({ is_saved: 1, page: 1 });
});
</script>
