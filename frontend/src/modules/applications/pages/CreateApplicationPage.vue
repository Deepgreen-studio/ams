<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
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
import ApplicationForm from '@/modules/applications/components/ApplicationForm.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const router = useRouter();
const applicationsStore = useApplicationsStore();

async function onSubmit(payload) {
  const application = await applicationsStore.createApplication(payload);
  await router.push({ name: 'applications.show', params: { id: application.uuid } });
}
</script>
