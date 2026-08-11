<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'applications.configurations.create', params: { id: route.params.id } }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Add configuration
      </RouterLink>
    </Teleport>

    <ApplicationSubnav :application-id="route.params.id" />

    <div class="mb-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
      <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-zinc-500">
        Environment scope
      </label>
      <SelectBox
        v-model="environmentFilter"
        wrapper-class="w-full max-w-md"
        size="lg"
        :options="environmentOptions"
        @change="reload"
      />
    </div>

    <div v-if="configurationsStore.loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-40 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!configurationsStore.configurations.length"
      title="No configurations"
      description="Create Feature Flags, Remote Config, Maintenance Mode, or key configurations for this scope."
    >
      <template #action>
        <RouterLink
          :to="{ name: 'applications.configurations.create', params: { id: route.params.id } }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Add configuration
        </RouterLink>
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in configurationsStore.configurations"
        :key="item.uuid"
        class="flex h-full flex-col rounded-[12px] bg-white p-5 ring-1 transition"
        :class="
          item.status === 'published'
            ? 'ring-brand-600 hover:ring-brand-700'
            : 'ring-zinc-100 hover:ring-brand-200'
        "
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="truncate text-base font-semibold tracking-tight text-slate-900">
              {{ item.name }}
            </h3>
            <p class="mt-1 text-sm text-slate-500">{{ item.type_label || item.type }}</p>
          </div>
          <span
            class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
            :class="statusClasses(item.status)"
          >
            <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(item.status)" />
            {{ statusLabel(item.status) }}
          </span>
        </div>

        <div class="mt-4 rounded-[12px] bg-zinc-50 px-3.5 py-3">
          <p class="text-xs font-medium text-zinc-500">Scope</p>
          <p class="mt-1 text-sm font-semibold text-slate-900">
            Version {{ item.version }} · {{ item.environment?.name || 'Application-wide' }}
          </p>
        </div>

        <div class="mt-5 flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
          <RouterLink
            :to="{
              name: 'applications.configurations.edit',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50"
          >
            Manage
          </RouterLink>
          <RouterLink
            v-if="item.type === 'feature_flags'"
            :to="{
              name: 'applications.configurations.flags',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-zinc-100"
          >
            Feature flags
          </RouterLink>
          <RouterLink
            :to="{
              name: 'applications.configurations.history',
              params: { id: route.params.id, configurationId: item.uuid },
            }"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-zinc-100"
          >
            History
          </RouterLink>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';
import { environmentService } from '@/modules/applications/services/environmentService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const configurationsStore = useConfigurationsStore();
const toast = useToast();
const environments = ref([]);
const environmentFilter = ref('');

const environmentOptions = computed(() => [
  { value: '', label: 'Application-wide' },
  ...environments.value.map((env) => ({
    value: env.uuid,
    label: `${env.name} (${env.type})`,
  })),
]);

watch(
  () => configurationsStore.error,
  (message) => {
    if (message) toast.error(message, 'Error');
  },
);

watch(
  () => configurationsStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

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
  try {
    await configurationsStore.fetchManager(route.params.id, environmentFilter.value || null);
  } catch {
    // Toast handled by watcher.
  }
}

function statusLabel(status) {
  return String(status || 'draft')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusClasses(status) {
  switch (status) {
    case 'published':
      return 'border-emerald-600 text-emerald-700';
    case 'archived':
      return 'border-slate-400 text-slate-600';
    default:
      return 'border-amber-500 text-amber-700';
  }
}

function statusDot(status) {
  switch (status) {
    case 'published':
      return 'bg-emerald-600';
    case 'archived':
      return 'bg-slate-400';
    default:
      return 'bg-amber-500';
  }
}
</script>
