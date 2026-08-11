<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.releases.calendar', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Calendar
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.timeline', params: { id: route.params.id } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Timeline
        </RouterLink>
        <RouterLink
          :to="{ name: 'applications.releases.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Plan release
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="releasesStore.summary" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <ReleaseTable
      :application-id="route.params.id"
      :releases="releasesStore.releases"
      :loading="releasesStore.loading"
    >
      <template #empty-action>
        <RouterLink
          :to="{ name: 'applications.releases.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Plan release
        </RouterLink>
      </template>
    </ReleaseTable>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  ArrowPathIcon,
  CheckCircleIcon,
  ClockIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import ReleaseTable from '@/modules/applications/components/ReleaseTable.vue';
import { useReleasesStore } from '@/modules/applications/stores/releases';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const releasesStore = useReleasesStore();
const toast = useToast();

const statCards = computed(() => [
  {
    label: 'Total',
    value: releasesStore.summary?.total ?? 0,
    icon: Squares2X2Icon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Awaiting approval',
    value: releasesStore.summary?.awaiting_approval ?? 0,
    icon: ClockIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    label: 'Deployed',
    value: releasesStore.summary?.deployed ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Rolled back',
    value: releasesStore.summary?.rolled_back ?? 0,
    icon: ArrowPathIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

watch(
  () => releasesStore.error,
  (message) => {
    if (message) toast.error(message, 'Unable to load releases');
  },
);

watch(
  () => releasesStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

onMounted(async () => {
  try {
    await releasesStore.fetchDashboard(route.params.id);
  } catch {
    // Toast handled by watcher.
  }
});
</script>
