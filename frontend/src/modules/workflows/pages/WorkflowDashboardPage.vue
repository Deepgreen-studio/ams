<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'workflows.queue' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Approval queue
      </RouterLink>
      <RouterLink
        :to="{ name: 'workflows.designer.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        New workflow
      </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="`def-${n}`" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in definitionCards"
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

    <div v-if="store.loading && !hasStats" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="`inst-${n}`" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in instanceCards"
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

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Workflow types</h2>
        <div v-if="store.loading && !store.catalog.types.length" class="space-y-3">
          <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <ul v-else-if="store.catalog.types.length" class="divide-y divide-zinc-100">
          <li
            v-for="item in store.catalog.types"
            :key="item.value"
            class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <span class="text-sm font-medium text-slate-900">{{ item.label }}</span>
            <span class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
              {{ item.value }}
            </span>
          </li>
        </ul>
        <p v-else class="py-8 text-center text-sm text-slate-500">No workflow types available.</p>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-slate-900">Step catalog</h2>
          <RouterLink
            :to="{ name: 'workflows.designer' }"
            class="text-xs font-medium text-brand-700 hover:underline"
          >
            Open designer
          </RouterLink>
        </div>
        <div v-if="store.loading && !store.catalog.step_types.length" class="space-y-3">
          <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <ul v-else-if="store.catalog.step_types.length" class="divide-y divide-zinc-100">
          <li
            v-for="item in store.catalog.step_types"
            :key="item.value"
            class="py-3.5 first:pt-0 last:pb-0"
          >
            <p class="text-sm font-medium text-slate-900">{{ item.label }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ item.value }}</p>
          </li>
        </ul>
        <p v-else class="py-8 text-center text-sm text-slate-500">No step types available.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  CheckBadgeIcon,
  CheckCircleIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  PlayCircleIcon,
  QueueListIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline';
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

const hasStats = computed(() => store.statistics != null || store.instanceStatistics != null);

const definitionCards = computed(() => [
  {
    label: 'Definitions',
    value: store.statistics?.total ?? 0,
    icon: DocumentDuplicateIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Active',
    value: store.statistics?.active ?? 0,
    icon: PlayCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Draft',
    value: store.statistics?.draft ?? 0,
    icon: DocumentTextIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
  {
    label: 'Enabled',
    value: store.statistics?.enabled ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
]);

const instanceCards = computed(() => [
  {
    label: 'Instances',
    value: store.instanceStatistics?.total ?? 0,
    icon: QueueListIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
  {
    label: 'In progress',
    value: store.instanceStatistics?.in_progress ?? 0,
    icon: PlayCircleIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Approved',
    value: store.instanceStatistics?.approved ?? 0,
    icon: CheckBadgeIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-500',
  },
  {
    label: 'Rejected',
    value: store.instanceStatistics?.rejected ?? 0,
    icon: XCircleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-500',
  },
]);

onMounted(() => store.fetchDashboard());
</script>
