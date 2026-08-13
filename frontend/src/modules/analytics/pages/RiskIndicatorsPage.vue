<template>
  <div>
    <AnalyticsSubnav />

    <EnterpriseFilterBar v-model="filters" :show-category="false" @apply="onApply" @reset="onApply" />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load risk indicators</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading the composite risk score again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Retry
      </button>
    </div>

    <template v-else-if="data">
      <div
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2">
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Overall risk</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ formatNumber(data.score ?? 0) }}</p>
            <p class="mt-1 text-xs capitalize text-slate-400">{{ data.level || 'healthy' }} posture</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]" :class="scoreIconBg">
            <ChartBarIcon class="h-5 w-5" :class="scoreIconColor" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Indicators</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ formatNumber((data.indicators || []).length) }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Signals contributing to the score</p>
          </div>
          <div class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-zinc-100">
            <ShieldExclamationIcon class="h-5 w-5 text-slate-500" />
          </div>
        </div>
      </div>

      <div
        v-if="!(data.indicators || []).length"
        class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
      >
        <p class="text-sm font-medium text-slate-900">No risk indicators</p>
        <p class="mt-1 text-xs text-slate-500">Security signals will appear here once activity is recorded.</p>
      </div>
      <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="item in data.indicators"
          :key="item.key"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ item.label }}</p>
              <span
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
                :class="badgeClass(item.severity)"
              >
                {{ item.severity }}
              </span>
            </div>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ formatNumber(item.value) }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="indicatorIconBg(item.severity)"
          >
            <ExclamationTriangleIcon class="h-5 w-5" :class="indicatorIconColor(item.severity)" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import {
  ChartBarIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const toast = useToast();
const data = computed(() => store.risk);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const riskLevel = computed(() => (data.value?.level || '').toLowerCase());

const healthMessage = computed(() => {
  const level = data.value?.level || 'healthy';
  const score = data.value?.score ?? 0;
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return `Composite risk is ${level} (score ${score}). Review elevated indicators below.`;
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return `Composite risk is ${level} (score ${score}). Monitor warning-level indicators.`;
  }
  return `Composite risk is ${level} (score ${score}).`;
});

const healthTone = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return 'bg-rose-50 text-rose-800 ring-1 ring-rose-100';
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') {
    return ShieldExclamationIcon;
  }
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') {
    return ExclamationTriangleIcon;
  }
  return ShieldCheckIcon;
});

const scoreIconBg = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') return 'bg-rose-50';
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') return 'bg-amber-50';
  return 'bg-emerald-50';
});

const scoreIconColor = computed(() => {
  if (riskLevel.value === 'critical' || riskLevel.value === 'high') return 'text-rose-500';
  if (riskLevel.value === 'medium' || riskLevel.value === 'elevated') return 'text-amber-500';
  return 'text-emerald-500';
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.risk) return;
    toast.error(message);
    store.error = null;
  },
);

function badgeClass(severity) {
  const map = {
    critical: 'bg-rose-50 text-rose-700 ring-rose-100',
    high: 'bg-rose-50 text-rose-700 ring-rose-100',
    warning: 'bg-amber-50 text-amber-700 ring-amber-100',
    info: 'bg-sky-50 text-sky-700 ring-sky-100',
    ok: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
  };
  return map[severity] || 'bg-zinc-50 text-slate-600 ring-zinc-200';
}

function indicatorIconBg(severity) {
  const map = {
    critical: 'bg-rose-50',
    high: 'bg-rose-50',
    warning: 'bg-amber-50',
    info: 'bg-sky-50',
    ok: 'bg-emerald-50',
  };
  return map[severity] || 'bg-zinc-100';
}

function indicatorIconColor(severity) {
  const map = {
    critical: 'text-rose-500',
    high: 'text-rose-500',
    warning: 'text-amber-500',
    info: 'text-sky-500',
    ok: 'text-emerald-500',
  };
  return map[severity] || 'text-slate-500';
}

function formatNumber(value) {
  return new Intl.NumberFormat().format(Number(value || 0));
}

function onApply(next) {
  Object.assign(filters, next);
  load();
}

function load() {
  store.fetchRisk({ ...filters }).catch(() => {});
}

onMounted(() => {
  store.error = null;
  load();
});
</script>
