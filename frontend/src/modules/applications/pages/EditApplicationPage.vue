<template>
  <div>
    <div
      v-if="applicationsStore.loading && !applicationsStore.currentApplication"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <ApplicationForm
        :initial="applicationsStore.currentApplication || {}"
        :loading="applicationsStore.saving"
        :errors="applicationsStore.fieldErrors"
        :error="applicationsStore.error || ''"
        submit-label="Save changes"
        hide-company
        @submit="onSubmit"
        @cancel="router.push({ name: 'applications.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ApplicationForm from '@/modules/applications/components/ApplicationForm.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const route = useRoute();
const router = useRouter();
const applicationsStore = useApplicationsStore();

onMounted(() => {
  applicationsStore.fetchApplication(route.params.id);
});

async function onSubmit(payload) {
  const { company_id, ...updatePayload } = payload;
  await applicationsStore.updateApplication(route.params.id, updatePayload);
  await router.push({ name: 'applications.show', params: { id: route.params.id } });
}
</script>
