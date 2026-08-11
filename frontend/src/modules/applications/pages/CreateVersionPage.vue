<template>
  <div>
    <ApplicationSubnav :application-id="route.params.id" />
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <VersionForm
        :loading="versionsStore.saving"
        :errors="versionsStore.fieldErrors"
        :error="versionsStore.error || ''"
        submit-label="Create version"
        @submit="onSubmit"
        @cancel="router.push({ name: 'applications.versions', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionForm from '@/modules/applications/components/VersionForm.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const router = useRouter();
const versionsStore = useVersionsStore();

async function onSubmit(payload) {
  try {
    await versionsStore.createVersion(route.params.id, payload);
    await router.push({ name: 'applications.versions', params: { id: route.params.id } });
  } catch {
    // Toast + field errors are handled by VersionForm.
  }
}
</script>
