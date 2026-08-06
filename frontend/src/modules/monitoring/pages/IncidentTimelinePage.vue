<template>
  <div>
    <PageHeader title="Incident Timeline" description="Monitoring logs and alert events in chronological order.">
      <template #actions>
        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="load">Refresh</button>
      </template>
    </PageHeader>
    <MonitoringSubnav />

    <div class="mb-4 flex flex-wrap gap-3">
      <select v-model="filters.category" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="load">
        <option value="">All categories</option>
        <option value="health">Health</option>
        <option value="api">API</option>
        <option value="webhook">Webhook</option>
        <option value="queue">Queue</option>
        <option value="job">Job</option>
        <option value="integration">Integration</option>
        <option value="server">Server</option>
        <option value="incident">Incident</option>
      </select>
      <select v-model="filters.level" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="load">
        <option value="">All levels</option>
        <option value="info">Info</option>
        <option value="warning">Warning</option>
        <option value="error">Error</option>
        <option value="critical">Critical</option>
      </select>
    </div>

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="space-y-3">
      <div v-if="store.loading && !store.timeline.length" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        Loading timeline…
      </div>
      <div v-else-if="!store.timeline.length" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
        No incidents recorded yet. Capture a snapshot or wait for alert triggers.
      </div>
      <article
        v-for="item in store.timeline"
        :key="`${item.kind}-${item.uuid}`"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <div class="flex flex-wrap items-center gap-2">
          <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium uppercase text-slate-600">{{ item.kind }}</span>
          <span class="rounded-md px-2 py-0.5 text-xs font-medium capitalize" :class="levelClass(item.level)">{{ item.level }}</span>
          <span class="text-xs capitalize text-slate-500">{{ item.category }}</span>
          <span class="ml-auto text-xs text-slate-400">{{ item.occurred_at }}</span>
        </div>
        <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ item.title }}</h3>
        <p class="mt-1 text-sm text-slate-600">{{ item.message }}</p>
      </article>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import MonitoringSubnav from '@/modules/monitoring/components/MonitoringSubnav.vue';
import { useMonitoringStore } from '@/modules/monitoring/stores/monitoring';

const store = useMonitoringStore();
const filters = reactive({ category: '', level: '' });

function levelClass(level) {
  if (level === 'critical' || level === 'error') return 'bg-rose-50 text-rose-700';
  if (level === 'warning') return 'bg-amber-50 text-amber-700';
  return 'bg-slate-100 text-slate-700';
}

async function load() {
  await store.fetchTimeline({
    category: filters.category || undefined,
    level: filters.level || undefined,
    limit: 75,
  });
}

onMounted(load);
</script>
