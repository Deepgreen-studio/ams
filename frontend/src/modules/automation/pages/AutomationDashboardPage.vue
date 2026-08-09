<template>
  <div>
    <!-- <PageHeader
      title="Automation Engine"
      description="Event, time, scheduled, and conditional automation across AMS."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'automation.rules' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          View rules
        </RouterLink>
        <RouterLink
          :to="{ name: 'automation.rules.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New rule
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'automation.rules' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          View rules
        </RouterLink>
        <RouterLink
          :to="{ name: 'automation.rules.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New rule
        </RouterLink>
    </Teleport>

    <AutomationSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Supported triggers</h2>
        <ul class="divide-y divide-slate-100">
          <li v-for="item in store.catalog.trigger_types" :key="item.value" class="flex items-center justify-between py-3">
            <span class="text-sm text-slate-800">{{ item.label }}</span>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ item.value }}</span>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Event catalog</h2>
          <RouterLink :to="{ name: 'automation.history' }" class="text-xs font-medium text-brand-700 hover:underline">
            View history
          </RouterLink>
        </div>
        <ul class="divide-y divide-slate-100">
          <li v-for="event in store.catalog.events" :key="event.value" class="py-3">
            <p class="text-sm font-medium text-slate-900">{{ event.label }}</p>
            <p class="text-xs text-slate-500">{{ event.description }}</p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();

const statCards = computed(() => [
  { label: 'Total rules', value: store.statistics?.total ?? 0 },
  { label: 'Enabled', value: store.statistics?.enabled ?? 0 },
  { label: 'Disabled', value: store.statistics?.disabled ?? 0 },
  { label: 'Runs (logs)', value: store.logStatistics?.total ?? 0 },
]);

onMounted(() => {
  store.fetchDashboard();
});
</script>
