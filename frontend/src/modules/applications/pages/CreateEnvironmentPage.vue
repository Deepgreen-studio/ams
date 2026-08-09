<template>
  <div>
    <!-- <PageHeader
      title="Add environment"
      description="Register a Development, Testing, Staging, Production, or Sandbox environment."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <EnvironmentForm
        :loading="environmentsStore.saving"
        :errors="environmentsStore.fieldErrors"
        :error="environmentsStore.error || ''"
        submit-label="Create environment"
        @submit="onSubmit"
        @cancel="
          router.push({ name: 'applications.environments', params: { id: route.params.id } })
        "
      />
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import EnvironmentForm from '@/modules/applications/components/EnvironmentForm.vue';
import { useEnvironmentsStore } from '@/modules/applications/stores/environments';

const route = useRoute();
const router = useRouter();
const environmentsStore = useEnvironmentsStore();

async function onSubmit(payload) {
  const environment = await environmentsStore.createEnvironment(route.params.id, payload);
  await router.push({
    name: 'applications.environments.show',
    params: { id: route.params.id, environmentId: environment.uuid },
  });
}
</script>
