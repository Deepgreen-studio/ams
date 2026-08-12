<template>
  <div>
    <WorkflowsSubnav />

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <h2 class="text-base font-semibold text-slate-900">Recent instances</h2>
        <p class="mt-1 text-sm text-slate-500">
          Live view of running and recently completed workflow instances.
        </p>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.monitorRecent.length"
        title="No instances yet"
        description="Start a workflow from the designer to begin monitoring runs."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <RouterLink
            :to="{ name: 'workflows.designer' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Open designer
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Subject</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Workflow</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Current stage</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Open</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.monitorRecent"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4 font-medium text-slate-900">{{ item.subject_label || '—' }}</td>
              <td class="px-5 py-4 text-slate-700">{{ item.workflow?.name || '—' }}</td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                  {{ item.status_label || item.status }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ item.current_step?.name || '—' }}</td>
              <td class="px-5 py-4 text-right">
                <RouterLink
                  :to="{ name: 'workflows.instances.show', params: { id: item.uuid } }"
                  class="text-sm font-medium text-brand-700 hover:underline"
                >
                  View
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckBadgeIcon,
  ClockIcon,
  PlayCircleIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const toast = useToast();

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

const hasStats = computed(() => store.instanceStatistics != null);

const cards = computed(() => [
  {
    label: 'Total',
    value: store.instanceStatistics?.total ?? 0,
    icon: QueueListIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'In progress',
    value: store.instanceStatistics?.in_progress ?? 0,
    icon: PlayCircleIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
  {
    label: 'Approved',
    value: store.instanceStatistics?.approved ?? 0,
    icon: CheckBadgeIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Timed out',
    value: store.instanceStatistics?.timed_out ?? 0,
    icon: ClockIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
]);

onMounted(() => store.fetchMonitor());
</script>
