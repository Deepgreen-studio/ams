<template>
  <div>
    <PageHeader title="Threat Timeline" description="Chronological security events: failed logins, role changes, GDPR, API errors." />
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
    <div v-else-if="data" class="space-y-3">
      <p class="text-sm text-slate-500">{{ data.meta?.total ?? 0 }} events</p>
      <div
        v-for="(item, idx) in data.items || []"
        :key="idx"
        class="flex gap-4 rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="mt-1 h-3 w-3 shrink-0 rounded-full" :class="severityDot(item.severity)" />
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p class="font-medium text-slate-900">{{ item.title }}</p>
            <span class="text-xs text-slate-400">{{ formatTime(item.occurred_at) }}</span>
          </div>
          <p class="mt-1 text-sm text-slate-600">{{ item.message }}</p>
          <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ item.kind }} · {{ item.severity }}</p>
        </div>
      </div>
      <div v-if="!(data.items || []).length" class="rounded-xl border border-dashed border-slate-200 py-16 text-center text-sm text-slate-500">
        No threat events in this period.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import SecurityAnalyticsSubnav from '@/modules/analytics/components/SecurityAnalyticsSubnav.vue';
import { useSecurityAnalyticsStore } from '@/modules/analytics/stores/securityAnalytics';

const store = useSecurityAnalyticsStore();
const data = computed(() => store.timeline);

const filters = reactive({
  from: new Date(Date.now() - 13 * 86400000).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
});

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

function formatTime(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

async function load() {
  await store.fetchTimeline({ ...filters });
}

onMounted(load);
</script>
