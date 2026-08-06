<template>
  <div>
    <PageHeader title="Feature Flag Manager" :description="configuration?.name || 'Toggle and edit feature flags for this configuration.'">
      <template #actions>
        <RouterLink
          v-if="configuration"
          :to="{ name: 'applications.configurations.edit', params: { id: route.params.id, configurationId: configuration.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          JSON editor
        </RouterLink>
      </template>
    </PageHeader>

    <ApplicationSubnav :application-id="route.params.id" />

    <div v-if="configurationsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ configurationsStore.error }}
    </div>
    <div v-if="configurationsStore.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ configurationsStore.successMessage }}
    </div>

    <div v-if="configurationsStore.loading && !configuration" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <div v-else-if="configuration && configuration.type !== 'feature_flags'" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      This configuration is not a feature flags document.
    </div>
    <FeatureFlagManager
      v-else-if="configuration"
      :flags="flags"
      :loading="configurationsStore.saving"
      @save="onSave"
      @toggle="onToggle"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import FeatureFlagManager from '@/modules/applications/components/FeatureFlagManager.vue';
import { useConfigurationsStore } from '@/modules/applications/stores/configurations';

const route = useRoute();
const configurationsStore = useConfigurationsStore();

const configuration = computed(() => configurationsStore.currentConfiguration);
const flags = computed(() => configuration.value?.payload?.flags || []);

onMounted(() => {
  configurationsStore.fetchConfiguration(route.params.id, route.params.configurationId);
});

async function onSave(flag) {
  await configurationsStore.upsertFeatureFlag(route.params.id, route.params.configurationId, flag);
}

async function onToggle(flag) {
  await configurationsStore.toggleFeatureFlag(
    route.params.id,
    route.params.configurationId,
    flag.key,
    !flag.enabled,
  );
}
</script>
