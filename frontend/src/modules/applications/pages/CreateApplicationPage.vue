<template>
  <div>
    <PageHeader
      title="Create application"
      description="Register a new customer application in the Application Management catalog."
    />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <ApplicationForm
        :loading="applicationsStore.saving"
        :errors="applicationsStore.fieldErrors"
        :error="applicationsStore.error || ''"
        submit-label="Create application"
        @submit="onSubmit"
        @cancel="router.push({ name: 'applications.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationForm from '@/modules/applications/components/ApplicationForm.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const router = useRouter();
const applicationsStore = useApplicationsStore();

async function onSubmit(payload) {
  const application = await applicationsStore.createApplication(payload);
  await router.push({ name: 'applications.show', params: { id: application.uuid } });
}
</script>
