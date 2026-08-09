<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Manage Development, Testing, Staging, Production, and Sandbox environments."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <select
            v-if="environmentsStore.environments.length"
            class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            :value="environmentsStore.currentEnvironment?.uuid || ''"
            :disabled="environmentsStore.saving"
            @change="onSwitchSelect"
          >
            <option value="" disabled>Switch environment</option>
            <option
              v-for="item in environmentsStore.environments"
              :key="item.uuid"
              :value="item.uuid"
            >
              {{ item.name }}{{ item.is_current ? ' (current)' : '' }}
            </option>
          </select>
          <RouterLink
            :to="{ name: 'applications.environments.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Add environment
          </RouterLink>
        </div>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <div class="flex flex-wrap items-center gap-2">
          <select
            v-if="environmentsStore.environments.length"
            class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            :value="environmentsStore.currentEnvironment?.uuid || ''"
            :disabled="environmentsStore.saving"
            @change="onSwitchSelect"
          >
            <option value="" disabled>Switch environment</option>
            <option
              v-for="item in environmentsStore.environments"
              :key="item.uuid"
              :value="item.uuid"
            >
              {{ item.name }}{{ item.is_current ? ' (current)' : '' }}
            </option>
          </select>
          <RouterLink
            :to="{ name: 'applications.environments.create', params: { id: route.params.id } }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Add environment
          </RouterLink>
        </div>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      v-if="environmentsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ environmentsStore.successMessage }}
    </div>
    <div
      v-if="environmentsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ environmentsStore.error }}
    </div>

    <div v-if="environmentsStore.summary" class="mb-4 grid gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">
          {{ environmentsStore.summary.total }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Healthy</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">
          {{ environmentsStore.summary.healthy }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Unhealthy</p>
        <p class="mt-1 text-2xl font-semibold text-rose-700">
          {{ environmentsStore.summary.unhealthy }}
        </p>
      </div>
    </div>

    <div v-if="environmentsStore.loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 3" :key="n" class="h-56 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!environmentsStore.environments.length"
      title="No environments"
      description="Create Development, Testing, Staging, Production, or Sandbox environments."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'applications.environments.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
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
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentCard from '@/modules/applications/components/EnvironmentCard.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';

const route = useRoute();
const environmentsStore = useEnvironmentsStore();

const title = computed(() => {
  const name = environmentsStore.application?.name;
  return name ? `${name} environments` : 'Environments';
});

onMounted(() => {
  environmentsStore.fetchDashboard(route.params.id);
});

async function onSwitch(environment) {
  await environmentsStore.switchEnvironment(route.params.id, environment.uuid);
}

async function onSwitchSelect(event) {
  const uuid = event.target.value;
  if (!uuid) return;
  await environmentsStore.switchEnvironment(route.params.id, uuid);
}

async function onHealthCheck(environment) {
  await environmentsStore.runHealthCheck(route.params.id, environment.uuid);
}
</script>
