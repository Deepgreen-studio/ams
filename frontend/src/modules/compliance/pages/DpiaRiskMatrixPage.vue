<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.dpia.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
      </RouterLink>
      <RouterLink
        :to="{ name: 'compliance.dpia.mitigation' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <ShieldExclamationIcon class="h-4 w-4" />
        Mitigation
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !store.riskMatrix" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !store.riskMatrix"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load risk matrix</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading likelihood and impact scores again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else>
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
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Likelihood × impact</h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Active risks plotted by scored likelihood and impact. Select a cell to inspect the register.
              </p>
            </div>
            <ul class="flex flex-wrap gap-2 text-[11px] font-medium uppercase tracking-wide">
              <li class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Low</li>
              <li class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">Medium</li>
              <li class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700">High</li>
              <li class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700">Critical</li>
            </ul>
          </div>

          <div class="overflow-x-auto">
            <div class="min-w-[36rem]">
              <p class="mb-2 text-center text-[11px] font-medium uppercase tracking-wide text-slate-400">
                Impact →
              </p>
              <div class="flex gap-3">
                <p
                  class="flex w-8 shrink-0 items-center justify-center text-[11px] font-medium uppercase tracking-wide text-slate-400"
                  style="writing-mode: vertical-rl; transform: rotate(180deg)"
                >
                  Likelihood →
                </p>
                <table class="w-full border-separate border-spacing-1.5 text-sm">
                  <thead>
                    <tr>
                      <th class="w-16 p-1" />
                      <th
                        v-for="impact in impacts"
                        :key="impact"
                        class="p-1 text-center text-xs font-medium text-slate-500"
                      >
                        {{ impact }}
                        <span class="mt-0.5 block text-[10px] font-normal normal-case text-slate-400">
                          {{ impactLabels[impact] }}
                        </span>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="likelihood in likelihoods" :key="likelihood">
                      <th class="p-1 text-left text-xs font-medium text-slate-500">
                        {{ likelihood }}
                        <span class="mt-0.5 block text-[10px] font-normal normal-case text-slate-400">
                          {{ likelihoodLabels[likelihood] }}
                        </span>
                      </th>
                      <td v-for="impact in impacts" :key="`${likelihood}-${impact}`">
                        <button
                          type="button"
                          class="flex h-20 w-full flex-col items-center justify-center rounded-[12px] px-2 py-2 transition"
                          :class="cellClass(likelihood, impact)"
                          @click="selectCell(likelihood, impact)"
                        >
                          <span class="text-lg font-bold tracking-tight">
                            {{ cell(likelihood, impact)?.count || 0 }}
                          </span>
                          <span class="text-[10px] font-semibold uppercase tracking-wide">
                            {{ cell(likelihood, impact)?.level || '—' }}
                          </span>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">{{ listTitle }}</h2>
              <p class="mt-0.5 text-xs text-slate-500">{{ listHint }}</p>
            </div>
            <button
              v-if="selectedKey"
              type="button"
              class="text-xs font-medium text-brand-700 hover:underline"
              @click="selectedKey = ''"
            >
              Show all
            </button>
          </div>

          <div v-if="store.loading && !visibleRisks.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!visibleRisks.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No risks in this view</p>
            <p class="mt-1 text-xs text-slate-500">Scored active risks will appear on the matrix.</p>
          </div>
          <ul v-else class="max-h-[32rem] divide-y divide-zinc-100 overflow-y-auto">
            <li
              v-for="risk in visibleRisks"
              :key="risk.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ risk.title }}</p>
                <p class="mt-1 text-xs text-slate-500">
                  {{ [risk.risk_number, risk.risk_score != null ? `Score ${risk.risk_score}` : null].filter(Boolean).join(' · ') }}
                </p>
              </div>
              <BreachSeverityBadge :severity="risk.risk_level || risk.level" :label="riskLevelLabel(risk)" />
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const store = useDpiaStore();
const toast = useToast();
const selectedKey = ref('');
const likelihoods = [5, 4, 3, 2, 1];
const impacts = [1, 2, 3, 4, 5];
const likelihoodLabels = {
  5: 'Almost certain',
  4: 'Likely',
  3: 'Possible',
  2: 'Unlikely',
  1: 'Rare',
};
const impactLabels = {
  1: 'Negligible',
  2: 'Minor',
  3: 'Moderate',
  4: 'Major',
  5: 'Severe',
};

const cellMap = computed(() => {
  const map = {};
  for (const item of store.riskMatrix?.cells || []) {
    map[`${item.likelihood}-${item.impact}`] = item;
  }
  return map;
});

const totals = computed(() => {
  const counts = { total: 0, low: 0, medium: 0, high: 0, critical: 0 };
  for (const item of store.riskMatrix?.cells || []) {
    const count = Number(item.count || 0);
    counts.total += count;
    if (counts[item.level] != null) {
      counts[item.level] += count;
    }
  }
  return counts;
});

const cards = computed(() => {
  const stats = totals.value;
  return [
    {
      label: 'Plotted risks',
      value: stats.total,
      hint: 'Active risks with likelihood and impact',
      icon: ShieldExclamationIcon,
      iconBg: stats.total ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: stats.total ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'Critical',
      value: stats.critical,
      hint: stats.critical ? 'Score 17–25' : 'No critical cells occupied',
      icon: ExclamationTriangleIcon,
      iconBg: stats.critical ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: stats.critical ? 'text-rose-500' : 'text-emerald-500',
    },
    {
      label: 'High',
      value: stats.high,
      hint: stats.high ? 'Score 10–16' : 'No high cells occupied',
      icon: ShieldExclamationIcon,
      iconBg: stats.high ? 'bg-orange-50' : 'bg-zinc-100',
      iconColor: stats.high ? 'text-orange-500' : 'text-slate-500',
    },
    {
      label: 'Low / medium',
      value: stats.low + stats.medium,
      hint: 'Managed or acceptable exposure',
      icon: ShieldCheckIcon,
      iconBg: stats.low + stats.medium ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: stats.low + stats.medium ? 'text-emerald-500' : 'text-slate-500',
    },
  ];
});

const selectedCell = computed(() => (selectedKey.value ? cellMap.value[selectedKey.value] : null));

const visibleRisks = computed(() => {
  if (selectedCell.value) {
    return selectedCell.value.risks || [];
  }
  return (store.riskMatrix?.cells || []).flatMap((item) => item.risks || []);
});

const listTitle = computed(() => {
  if (!selectedCell.value) {
    return 'All plotted risks';
  }
  return `${selectedCell.value.level || 'Cell'} · L${selectedCell.value.likelihood} × I${selectedCell.value.impact}`;
});

const listHint = computed(() => {
  if (!selectedCell.value) {
    return 'Select a matrix cell to filter this list.';
  }
  return `Score ${selectedCell.value.score || selectedCell.value.likelihood * selectedCell.value.impact}`;
});

const healthMessage = computed(() => {
  const stats = totals.value;
  if (stats.critical > 0) {
    return `${stats.critical} critical risk${stats.critical === 1 ? '' : 's'} on the matrix need immediate mitigation.`;
  }
  if (stats.high > 0) {
    return `${stats.high} high risk${stats.high === 1 ? '' : 's'} should be reviewed against the mitigation plan.`;
  }
  if (stats.total > 0) {
    return `${stats.total} active risk${stats.total === 1 ? '' : 's'} plotted. Exposure is within low and medium bands.`;
  }
  return 'No scored active risks on the register. The matrix is ready for new assessments.';
});

const healthTone = computed(() => {
  const stats = totals.value;
  if (stats.critical > 0) return 'bg-rose-50 text-rose-800';
  if (stats.high > 0) return 'bg-amber-50 text-amber-800';
  if (stats.total > 0) return 'bg-sky-50 text-sky-800';
  return 'bg-emerald-50 text-emerald-800';
});

const healthIcon = computed(() => {
  const stats = totals.value;
  if (stats.critical > 0) return ExclamationTriangleIcon;
  if (stats.high > 0 || stats.total > 0) return ShieldExclamationIcon;
  return CheckCircleIcon;
});

onMounted(() => {
  reload();
});

function cell(likelihood, impact) {
  return cellMap.value[`${likelihood}-${impact}`];
}

function selectCell(likelihood, impact) {
  const key = `${likelihood}-${impact}`;
  selectedKey.value = selectedKey.value === key ? '' : key;
}

function cellClass(likelihood, impact) {
  const item = cell(likelihood, impact);
  const level = item?.level;
  const occupied = Number(item?.count || 0) > 0;
  const selected = selectedKey.value === `${likelihood}-${impact}`;
  const palette = {
    low: occupied ? 'bg-emerald-100 text-emerald-800' : 'bg-emerald-50 text-emerald-700',
    medium: occupied ? 'bg-amber-100 text-amber-800' : 'bg-amber-50 text-amber-700',
    high: occupied ? 'bg-orange-100 text-orange-800' : 'bg-orange-50 text-orange-700',
    critical: occupied ? 'bg-rose-100 text-rose-800' : 'bg-rose-50 text-rose-700',
  };

  return [
    palette[level] || 'bg-zinc-50 text-slate-600',
    occupied ? 'hover:brightness-95' : 'hover:bg-white',
    selected ? 'ring-2 ring-brand-600' : 'ring-1 ring-transparent',
  ];
}

function riskLevelLabel(risk) {
  const value = risk.risk_level_label || risk.risk_level || risk.level || '';
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
}

async function reload() {
  try {
    await store.fetchRiskMatrix();
  } catch {
    toast.error(store.error || 'Unable to load risk matrix');
  }
}
</script>
