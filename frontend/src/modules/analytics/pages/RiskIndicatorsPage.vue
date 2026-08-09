<template>
  <div>
    <!-- <PageHeader title="Risk Indicators" description="Composite risk score and severity indicators across security signals." /> -->
    <AnalyticsSubnav />
    <SecurityAnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        From
        <input v-model="filters.from" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        To
        <input v-model="filters.to" type="date" class="mt-1 block rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Apply</button>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div v-if="store.loading && !data" class="h-40 animate-pulse rounded-xl bg-slate-100" />
    <template v-else-if="data">
      <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Overall risk</p>
        <div class="mt-2 flex items-end gap-4">
          <p class="text-5xl font-semibold text-slate-900">{{ data.score ?? 0 }}</p>
          <p class="mb-1 text-lg font-medium capitalize" :class="levelClass">{{ data.level }}</p>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="item in data.indicators || []"
          :key="item.key"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-800">{{ item.label }}</p>
            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="badgeClass(item.severity)">
              {{ item.severity }}
            </span>
          </div>
          <p class="mt-3 text-3xl font-semibold text-slate-900">{{ item.value }}</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const data = computed(() => store.risk);

const filters = reactive({
  from: new Date(Date.now() - 29 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

const levelClass = computed(() => {
  const level = (data.value?.level || '').toLowerCase();
  if (level === 'critical' || level === 'high') return 'text-rose-600';
  if (level === 'medium' || level === 'elevated') return 'text-amber-600';
  return 'text-emerald-600';
});

function badgeClass(severity) {
  const map = {
    critical: 'bg-rose-100 text-rose-700',
    high: 'bg-rose-50 text-rose-700',
    warning: 'bg-amber-50 text-amber-700',
    info: 'bg-sky-50 text-sky-700',
    ok: 'bg-emerald-50 text-emerald-700',
  };
  return map[severity] || 'bg-slate-100 text-slate-600';
}

async function load() {
  await store.fetchRisk({ ...filters });
}

onMounted(load);
</script>
