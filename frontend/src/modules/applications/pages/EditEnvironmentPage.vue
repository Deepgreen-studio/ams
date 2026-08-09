<template>
  <div>
    <!-- <PageHeader
      title="Edit environment"
      description="Update URLs, status, and encrypted environment variables."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />
    <div
      v-if="environmentsStore.loading && !environmentsStore.selectedEnvironment"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <EnvironmentForm
        :initial="environmentsStore.selectedEnvironment || {}"
        :loading="environmentsStore.saving"
        :errors="environmentsStore.fieldErrors"
        :error="environmentsStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="
          router.push({
            name: 'applications.environments.show',
            params: { id: route.params.id, environmentId: route.params.environmentId },
          })
        "
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentForm from '@/modules/applications/components/EnvironmentForm.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';

const route = useRoute();
const router = useRouter();
const environmentsStore = useEnvironmentsStore();

onMounted(() => {
  environmentsStore.fetchEnvironment(route.params.id, route.params.environmentId);
});

async function onSubmit(payload) {
  await environmentsStore.updateEnvironment(route.params.id, route.params.environmentId, payload);
  await router.push({
    name: 'applications.environments.show',
    params: { id: route.params.id, environmentId: route.params.environmentId },
  });
}
</script>
