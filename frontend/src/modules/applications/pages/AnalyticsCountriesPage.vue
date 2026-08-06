<template>
  <div>
    <PageHeader
      title="Country statistics"
      description="User, session, and install distribution by country."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.analytics', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >Dashboard</RouterLink
        >
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ analyticsStore.error }}
    </div>

    <div class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="item in analyticsStore.countries"
        :key="item.country_code"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-sm font-semibold text-slate-900">
              {{ item.country_name || item.country_code }}
            </p>
            <p class="text-xs text-slate-500">{{ item.country_code }}</p>
          </div>
          <span class="rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800">{{
            intensity(item.users)
          }}</span>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
          <div
            class="h-full rounded-full bg-teal-600"
            :style="{ width: `${barWidth(item.users)}%` }"
          />
        </div>
        <p class="mt-2 text-xs text-slate-600">
          {{ item.users }} users · {{ item.sessions }} sessions · {{ item.installs }} installs
        </p>
      </div>
    </div>

    <EmptyState
      v-if="!analyticsStore.loading && !analyticsStore.countries.length"
      title="No country data"
      description="Ingest analytics with country breakdowns to see geo statistics."
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';

const route = useRoute();
const analyticsStore = useAnalyticsStore();

const maxUsers = computed(() =>
  Math.max(1, ...analyticsStore.countries.map((item) => Number(item.users) || 0)),
);

onMounted(() => analyticsStore.fetchCountries(route.params.id));

function barWidth(users) {
  return Math.round(((Number(users) || 0) / maxUsers.value) * 100);
}

function intensity(users) {
  const ratio = (Number(users) || 0) / maxUsers.value;
  if (ratio >= 0.66) return 'High';
  if (ratio >= 0.33) return 'Medium';
  return 'Low';
}
</script>
