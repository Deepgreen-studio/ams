<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          v-for="link in navLinks"
          :key="link.name"
          :to="{ name: link.name, params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] px-5 py-2.5 text-sm font-medium transition"
          :class="
            isActive(link.name)
              ? 'bg-brand-600 text-white hover:bg-brand-700'
              : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
          "
        >
          {{ link.label }}
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="analyticsStore.loading && !analyticsStore.countries.length"
      class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
      <div v-for="n in 6" :key="n" class="h-32 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div
      v-else-if="analyticsStore.countries.length"
      class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
      <div
        v-for="item in analyticsStore.countries"
        :key="item.country_code"
        class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-base font-semibold text-slate-900">
              {{ item.country_name || item.country_code }}
            </p>
            <p class="mt-0.5 text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ item.country_code }}
            </p>
          </div>
          <span
            class="inline-flex shrink-0 rounded-[12px] px-2.5 py-1 text-xs font-medium"
            :class="intensityClass(item.users)"
          >
            {{ intensity(item.users) }}
          </span>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100">
          <div
            class="h-full rounded-full bg-brand-600"
            :style="{ width: `${barWidth(item.users)}%` }"
          />
        </div>
        <p class="mt-3 text-xs text-slate-600">
          {{ item.users }} users · {{ item.sessions }} sessions · {{ item.installs }} installs
        </p>
      </div>
    </div>

    <div
      v-else-if="!analyticsStore.loading"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <EmptyState
        title="No country data"
        description="Ingest analytics with country breakdowns to see geo statistics."
        class="px-6 py-10"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useAnalyticsStore } from '@/modules/applications/stores/analytics';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const analyticsStore = useAnalyticsStore();
const toast = useToast();

const navLinks = [
  { name: 'applications.analytics', label: 'Dashboard' },
  { name: 'applications.analytics.trends', label: 'Trends' },
  { name: 'applications.analytics.heatmap', label: 'Heatmap' },
  { name: 'applications.analytics.countries', label: 'Countries' },
  { name: 'applications.analytics.devices', label: 'Devices' },
];

const maxUsers = computed(() =>
  Math.max(1, ...analyticsStore.countries.map((item) => Number(item.users) || 0)),
);

function isActive(name) {
  return route.name === name;
}

function barWidth(users) {
  return Math.round(((Number(users) || 0) / maxUsers.value) * 100);
}

function intensity(users) {
  const ratio = (Number(users) || 0) / maxUsers.value;
  if (ratio >= 0.66) return 'High';
  if (ratio >= 0.33) return 'Medium';
  return 'Low';
}

function intensityClass(users) {
  const ratio = (Number(users) || 0) / maxUsers.value;
  if (ratio >= 0.66) return 'bg-emerald-50 text-emerald-700';
  if (ratio >= 0.33) return 'bg-amber-50 text-amber-700';
  return 'bg-zinc-100 text-zinc-600';
}

watch(
  () => analyticsStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load country analytics');
  },
);

onMounted(() => analyticsStore.fetchCountries(route.params.id));
</script>
