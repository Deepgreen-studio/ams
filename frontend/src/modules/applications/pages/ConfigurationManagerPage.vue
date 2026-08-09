<template>
  <div>
    <!-- <PageHeader
      :title="title"
      description="Manage feature flags, remote config, maintenance mode, and API keys as validated JSON."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'applications.configurations.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add configuration
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'applications.configurations.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add configuration
        </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:flex-row lg:items-end"
    >
      <div class="w-full lg:w-72">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Environment scope</label
        >
        <select
          v-model="environmentFilter"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          @change="reload"
        >
          <option value="">Application-wide</option>
          <option v-for="env in environments" :key="env.uuid" :value="env.uuid">
            {{ env.name }} ({{ env.type }})
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="configurationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ configurationsStore.error }}
    </div>
    <div
      v-if="configurationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ configurationsStore.successMessage }}
    </div>

    <div v-if="configurationsStore.loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-36 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!configurationsStore.configurations.length"
      title="No configurations"
      description="Create Feature Flags, Remote Config, Maintenance Mode, or key configurations for this scope."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'applications.configurations.create', params: { id: route.params.id } }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add configuration
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in configurationsStore.configurations"
        :key="item.uuid"
        class="rounded-xl border border-slate-200 bg-white p-5"
      >
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="text-base font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ item.type_label || item.type }}</p>
          </div>
          <span
            class="rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
            :class="statusClass(item.status)"
          >
            {{ item.status }}
          </span>
        </div>
        <p class="mt-3 text-xs text-slate-500">
          Version {{ item.version }} · {{ item.environment?.name || 'Application-wide' }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
          <RouterLink
            :to="{
              name: 'applications.configurations.edit',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
          >
            Manage
          </RouterLink>
          <RouterLink
            v-if="item.type === 'feature_flags'"
            :to="{
              name: 'applications.configurations.flags',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
          >
            Feature flags
          </RouterLink>
          <RouterLink
            :to="{
              name: 'applications.configurations.history',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
          >
            History
          </RouterLink>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { environmentService } from '@/modules/applications/services/environmentService';

const route = useRoute();
const configurationsStore = useConfigurationsStore();
const environments = ref([]);
const environmentFilter = ref('');

const title = computed(() => {
  const name = configurationsStore.application?.name;
  return name ? `${name} configuration` : 'Configuration Manager';
});

onMounted(async () => {
  try {
    const { data } = await environmentService.dashboard(route.params.id);
    environments.value = data.data?.environments ?? [];
  } catch {
    environments.value = [];
  }
  await reload();
});

async function reload() {
  await configurationsStore.fetchManager(route.params.id, environmentFilter.value || null);
}

function statusClass(status) {
  switch (status) {
    case 'published':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'archived':
      return 'bg-slate-50 text-slate-600 ring-slate-500/20';
    default:
      return 'bg-amber-50 text-amber-800 ring-amber-600/20';
  }
}
</script>
