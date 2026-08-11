<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center justify-end gap-2">
        <SelectBox
          v-if="switchOptions.length"
          :model-value="environmentsStore.currentEnvironment?.uuid || ''"
          wrapper-class="min-w-[12rem]"
          placeholder="Switch environment"
          :options="switchOptions"
          :disabled="environmentsStore.saving"
          @change="onSwitchSelect"
        />
        <RouterLink
          :to="{ name: 'applications.environments.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add environment
        </RouterLink>
      </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="environmentsStore.summary" class="mb-4 grid gap-4 sm:grid-cols-3">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight" :class="card.valueClass">
            {{ card.value }}
          </p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div v-if="environmentsStore.loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-56 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!environmentsStore.environments.length"
      title="No environments"
      description="Create Development, Testing, Staging, Production, or Sandbox environments."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'applications.environments.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add environment
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <EnvironmentCard
        v-for="item in environmentsStore.environments"
        :key="item.uuid"
        :application-id="route.params.id"
        :environment="item"
        :switching="environmentsStore.saving"
        :checking="environmentsStore.saving"
        @switch="onSwitch"
        @health-check="onHealthCheck"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ServerStackIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentCard from '@/modules/applications/components/EnvironmentCard.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const environmentsStore = useEnvironmentsStore();
const toast = useToast();

const switchOptions = computed(() =>
  environmentsStore.environments.map((item) => ({
    value: item.uuid,
    label: item.is_current ? `${item.name} (current)` : item.name,
  })),
);

const statCards = computed(() => [
  {
    label: 'Total',
    value: environmentsStore.summary?.total ?? 0,
    valueClass: 'text-slate-900',
    icon: ServerStackIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Healthy',
    value: environmentsStore.summary?.healthy ?? 0,
    valueClass: 'text-emerald-700',
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Unhealthy',
    value: environmentsStore.summary?.unhealthy ?? 0,
    valueClass: 'text-rose-700',
    icon: ExclamationTriangleIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

watch(
  () => environmentsStore.error,
  (message) => {
    if (message) {
      toast.error(message, 'Error');
    }
  },
);

watch(
  () => environmentsStore.successMessage,
  (message) => {
    if (message) {
      toast.success(message);
    }
  },
);

onMounted(() => {
  environmentsStore.fetchDashboard(route.params.id);
});

async function onSwitch(environment) {
  try {
    await environmentsStore.switchEnvironment(route.params.id, environment.uuid);
  } catch {
    // Toast handled by store error watcher.
  }
}

async function onSwitchSelect(uuid) {
  if (!uuid || uuid === environmentsStore.currentEnvironment?.uuid) return;
  try {
    await environmentsStore.switchEnvironment(route.params.id, uuid);
  } catch {
    // Toast handled by store error watcher.
  }
}

async function onHealthCheck(environment) {
  try {
    await environmentsStore.runHealthCheck(route.params.id, environment.uuid);
  } catch {
    // Toast handled by store error watcher.
  }
}
</script>
