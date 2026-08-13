<template>
  <div>
    <AnalyticsSubnav />

    <EnterpriseFilterBar v-model="filters" :show-category="false" @apply="onApply" @reset="onApply" />

    <div v-if="store.loading && !data" class="space-y-4">
      <div class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load threat timeline</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading failed logins, role changes, and API errors again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else-if="data">
      <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Events</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ formatNumber(data.meta?.total ?? (data.items || []).length) }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Chronological security signals</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-brand-50">
            <ClockIcon class="h-5 w-5 text-brand-500" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Elevated</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ formatNumber(elevatedCount) }}</p>
            <p class="mt-1 text-xs text-slate-400">Critical, high, or warning severity</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="elevatedCount ? 'bg-rose-50' : 'bg-zinc-100'"
          >
            <ShieldExclamationIcon class="h-5 w-5" :class="elevatedCount ? 'text-rose-500' : 'text-slate-500'" />
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
          <h2 class="text-base font-semibold text-slate-900">Threat timeline</h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Failed logins, role changes, GDPR activity, and API errors in chronological order.
          </p>
        </div>

        <div v-if="!(data.items || []).length" class="px-6 py-16 text-center sm:px-8">
          <p class="text-sm font-medium text-slate-900">No threat events in this period</p>
          <p class="mt-1 text-xs text-slate-500">Adjust the date range or wait for new security activity.</p>
        </div>
        <ol v-else class="divide-y divide-zinc-100">
          <li
            v-for="(item, idx) in data.items"
            :key="idx"
            class="flex gap-4 px-6 py-5 sm:px-8"
          >
            <div class="relative flex w-8 shrink-0 flex-col items-center">
              <span class="z-10 mt-1 h-3 w-3 rounded-full" :class="severityDot(item.severity)" />
              <span
                v-if="idx < data.items.length - 1"
                class="absolute top-5 h-full w-px bg-zinc-200"
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                  <p class="mt-1 text-sm text-slate-600">{{ item.message }}</p>
                </div>
                <span
                  class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                  :class="severityBadge(item.severity)"
                >
                  {{ item.severity }}
                </span>
              </div>
              <p class="mt-2 text-xs uppercase tracking-wide text-slate-400">
                {{ item.kind }} · {{ formatTime(item.occurred_at) }}
              </p>
            </div>
          </li>
        </ol>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { ClockIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const toast = useToast();
const data = computed(() => store.timeline);

const filters = reactive({
  from: new Date(Date.now() - 13 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const elevatedCount = computed(() =>
  (data.value?.items || []).filter((item) => ['critical', 'high', 'warning'].includes(item.severity)).length,
);

watch(
  () => store.error,
  (message) => {
    if (!message || !store.timeline) return;
    toast.error(message);
    store.error = null;
  },
);

function severityDot(severity) {
  const map = {
    critical: 'bg-rose-600',
    high: 'bg-rose-500',
    warning: 'bg-amber-500',
    info: 'bg-sky-500',
    ok: 'bg-emerald-500',
  };
  return map[severity] || 'bg-slate-400';
}

function severityBadge(severity) {
  const map = {
    critical: 'bg-rose-50 text-rose-700 ring-rose-100',
    high: 'bg-rose-50 text-rose-700 ring-rose-100',
    warning: 'bg-amber-50 text-amber-700 ring-amber-100',
    info: 'bg-sky-50 text-sky-700 ring-sky-100',
    ok: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
  };
  return map[severity] || 'bg-zinc-50 text-slate-600 ring-zinc-200';
}

function formatTime(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function load() {
  store.fetchTimeline({ ...filters }).catch(() => {});
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
