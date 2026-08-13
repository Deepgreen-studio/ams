<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'analytics.executive' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        CEO dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'analytics.executive.trends' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <ChartBarIcon class="h-4 w-4" />
        Trends
      </RouterLink>
    </Teleport>

    <AnalyticsSubnav />

    <EnterpriseFilterBar
      :model-value="filters"
      :show-category="false"
      @apply="onApply"
      @reset="onApply"
    />

    <div v-if="store.loading && !data" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 8" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !data"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load scorecards</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading composite performance scores again.</p>
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
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in data.scorecards || []"
          :key="card.key"
          class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
              <div
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
                :class="scorecardIcon(card).bg"
              >
                <component :is="scorecardIcon(card).icon" class="h-5 w-5" :class="scorecardIcon(card).color" />
              </div>
              <p class="truncate text-sm font-medium text-slate-800">{{ card.label }}</p>
            </div>
            <span
              class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
              :class="statusClass(card.status)"
            >
              {{ card.status }}
            </span>
          </div>
          <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ card.score }}</p>
          <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100">
            <div
              class="h-full rounded-full"
              :class="scoreBarClass(card.status)"
              :style="{ width: `${Math.min(100, Number(card.score || 0))}%` }"
            />
          </div>
          <p class="mt-2 text-xs text-slate-500">{{ card.unit_label }}: {{ formatScorecardValue(card) }}</p>
        </div>
      </div>

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="border-b border-zinc-100 px-6 py-5">
          <h2 class="text-base font-semibold text-slate-900">Performance indicators</h2>
          <p class="mt-0.5 text-xs text-slate-500">Period values with change versus the prior snapshot.</p>
        </div>
        <div v-if="!(data.performance || []).length" class="px-6 py-16 text-center">
          <p class="text-sm font-medium text-slate-900">No performance indicators</p>
          <p class="mt-1 text-xs text-slate-500">Capture an executive snapshot to seed these metrics.</p>
        </div>
        <div v-else class="grid gap-3 p-6 sm:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="item in data.performance"
            :key="item.key"
            class="rounded-[12px] bg-zinc-50 px-4 py-3 ring-1 ring-zinc-100"
          >
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ item.label }}</p>
            <p class="mt-1 text-xl font-bold tracking-tight text-slate-900">{{ formatValue(item) }}</p>
            <p v-if="item.delta?.change != null" class="mt-1 text-xs text-slate-500">
              Δ {{ item.delta.change }} ({{ item.delta.change_percent ?? '—' }}%)
            </p>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArrowTrendingUpIcon,
  ChartBarIcon,
  CheckCircleIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  HeartIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  Squares2X2Icon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import EnterpriseFilterBar from '@/modules/analytics/components/EnterpriseFilterBar.vue';
import { useExecutiveAnalyticsStore } from '@/modules/analytics/stores/executiveAnalytics';

const store = useExecutiveAnalyticsStore();
const toast = useToast();
const data = computed(() => store.scorecards);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const scorecards = computed(() => data.value?.scorecards || []);
const businessCard = computed(() => scorecards.value.find((card) => card.key === 'business'));
const businessScore = computed(() => Number(businessCard.value?.score ?? data.value?.kpis?.business_score ?? 0));
const criticalCount = computed(() => scorecards.value.filter((card) => card.status === 'critical').length);
const watchCount = computed(() => scorecards.value.filter((card) => card.status === 'watch').length);

const healthMessage = computed(() => {
  if (criticalCount.value) {
    const noun = criticalCount.value === 1 ? 'scorecard is' : 'scorecards are';
    return `${criticalCount.value} ${noun} critical. Business score is ${businessScore.value}.`;
  }
  if (watchCount.value) {
    const noun = watchCount.value === 1 ? 'scorecard is' : 'scorecards are';
    return `${watchCount.value} ${noun} on watch. Review before they degrade.`;
  }
  if (businessScore.value < 60) {
    return `Business score is ${businessScore.value}. Composite performance needs attention.`;
  }
  return 'Scorecards are healthy across revenue, growth, support, and operations.';
});

const healthTone = computed(() => {
  if (criticalCount.value || businessScore.value < 40) {
    return 'bg-rose-50 text-rose-800 ring-1 ring-rose-100';
  }
  if (watchCount.value || businessScore.value < 60) {
    return 'bg-amber-50 text-amber-800 ring-1 ring-amber-100';
  }
  return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100';
});

const healthIcon = computed(() => {
  if (criticalCount.value || businessScore.value < 40) {
    return ExclamationTriangleIcon;
  }
  if (watchCount.value || businessScore.value < 60) {
    return ChartBarIcon;
  }
  return CheckCircleIcon;
});

watch(
  () => store.error,
  (message) => {
    if (!message || !store.scorecards) return;
    toast.error(message);
    store.error = null;
  },
);

function formatMoney(value) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function formatScorecardValue(card) {
  if (card.key === 'revenue') return formatMoney(card.value);
  return card.value;
}

function formatValue(item) {
  if (item.unit === 'currency') {
    return formatMoney(item.value);
  }
  return item.value;
}

function statusClass(status) {
  const map = {
    excellent: 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20',
    good: 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20',
    watch: 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
    critical: 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20',
  };
  return map[status] || 'bg-zinc-50 text-slate-600 ring-1 ring-inset ring-zinc-200';
}

function scoreBarClass(status) {
  const map = {
    excellent: 'bg-emerald-500',
    good: 'bg-sky-500',
    watch: 'bg-amber-500',
    critical: 'bg-rose-500',
  };
  return map[status] || 'bg-brand-600';
}

function scorecardIcon(card) {
  const icons = {
    revenue: CurrencyDollarIcon,
    growth: ArrowTrendingUpIcon,
    customers: UserGroupIcon,
    support: ClockIcon,
    compliance: ShieldCheckIcon,
    operations: HeartIcon,
    security: LockClosedIcon,
    business: ChartBarIcon,
  };
  const tones = {
    excellent: { bg: 'bg-emerald-50', color: 'text-emerald-500' },
    good: { bg: 'bg-sky-50', color: 'text-sky-500' },
    watch: { bg: 'bg-amber-50', color: 'text-amber-500' },
    critical: { bg: 'bg-rose-50', color: 'text-rose-500' },
  };
  const tone = tones[card.status] || { bg: 'bg-zinc-100', color: 'text-slate-500' };

  return {
    icon: icons[card.key] || ChartBarIcon,
    bg: tone.bg,
    color: tone.color,
  };
}

function onApply(next) {
  filters.from = next.from;
  filters.to = next.to;
  load();
}

function load() {
  return store.fetchScorecards({ from: filters.from, to: filters.to }).catch(() => {});
}

onMounted(load);
</script>
