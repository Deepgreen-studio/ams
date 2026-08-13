<template>
  <div>
    <AnalyticsSubnav />

    <AnalyticsFilterBar
      v-model="store.filters"
      :exporting="store.exporting"
      @apply="onApply"
      @reset="onApply"
      @export="(format) => store.exportReport(format, 'ai')"
    />

    <div v-if="store.loading && !store.ai" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.ai"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load AI usage analytics</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading request volume, tokens, and latency again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else-if="store.ai">
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="mb-4">
        <SimpleLineChart
          title="AI requests"
          hint="Daily volume"
          :labels="store.ai.trends?.labels || []"
          :series="[{ key: 'requests', label: 'Requests', values: store.ai.trends?.requests || [] }]"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">By feature</h2>
            <p class="mt-0.5 text-xs text-slate-500">Request volume and tokens by product feature.</p>
          </div>
          <div v-if="!(store.ai.by_feature || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No usage</p>
            <p class="mt-1 text-xs text-slate-500">Feature-level AI usage will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in store.ai.by_feature"
              :key="row.feature"
              class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
            >
              <span class="truncate text-sm text-slate-700">{{ row.feature }}</span>
              <span class="text-sm font-medium text-slate-900">
                {{ formatNumber(row.total) }} · {{ formatNumber(row.tokens) }} tok
              </span>
            </li>
          </ul>
        </section>

        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
          <div class="border-b border-zinc-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">By driver</h2>
            <p class="mt-0.5 text-xs text-slate-500">Request volume and tokens by model driver.</p>
          </div>
          <div v-if="!(store.ai.by_driver || []).length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No usage</p>
            <p class="mt-1 text-xs text-slate-500">Driver-level AI usage will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-50 px-3 py-2">
            <li
              v-for="row in store.ai.by_driver"
              :key="row.driver || 'none'"
              class="flex items-center justify-between gap-3 rounded-[12px] px-3 py-3"
            >
              <span class="truncate text-sm text-slate-700">{{ row.driver || 'n/a' }}</span>
              <span class="text-sm font-medium text-slate-900">
                {{ formatNumber(row.total) }} · {{ formatNumber(row.tokens) }} tok
              </span>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import {
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  CheckCircleIcon,
  ClockIcon,
  SparklesIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import SimpleLineChart from '@/modules/applications/components/SimpleLineChart.vue';
import AnalyticsFilterBar from '@/modules/analytics/components/AnalyticsFilterBar.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useAnalyticsStore } from '@/modules/analytics/stores/analytics';

const store = useAnalyticsStore();
const toast = useToast();

const cards = computed(() => {
  const data = store.ai || {};
  return [
    kpi('Requests', data.requests, 'AI calls in period', SparklesIcon, 'violet'),
    kpi('Tokens in', data.tokens_in, 'Prompt tokens', ArrowDownTrayIcon, 'sky'),
    kpi('Tokens out', data.tokens_out, 'Completion tokens', ArrowUpTrayIcon, 'brand'),
    kpi('Avg latency (ms)', data.avg_latency_ms, 'Mean response time', ClockIcon, 'amber'),
    kpi('Success', data.success_count, 'Completed requests', CheckCircleIcon, 'emerald'),
    kpi('Failed', data.failed_count, 'Errored requests', XCircleIcon, 'rose'),
  ];
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.ai) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

function kpi(label, value, hint, icon, tone) {
  const numeric = Number(value || 0);
  const tones = {
    brand: ['bg-brand-50', 'text-brand-500'],
    rose: ['bg-rose-50', 'text-rose-500'],
    sky: ['bg-sky-50', 'text-sky-500'],
    emerald: ['bg-emerald-50', 'text-emerald-500'],
    amber: ['bg-amber-50', 'text-amber-500'],
    violet: ['bg-violet-50', 'text-violet-500'],
  };
  const [iconBg, iconColor] = numeric ? tones[tone] : ['bg-zinc-100', 'text-slate-500'];

  return {
    label,
    value: formatNumber(value),
    hint,
    icon,
    iconBg,
    iconColor,
  };
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  store.filters = { ...store.filters, ...next };
  store.fetchAi().catch(() => {});
}

function reload() {
  store.fetchAi().catch(() => {});
}

onMounted(() => {
  store.error = null;
  store.successMessage = null;
  store.fetchAi().catch(() => {});
});
</script>
