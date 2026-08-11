<template>
  <div>
    <ApplicationSubnav :application-id="route.params.id" />
    <div
      v-if="versionsStore.loading && !versionsStore.currentVersion"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <VersionForm
        :initial="versionsStore.currentVersion || {}"
        :loading="versionsStore.saving"
        :errors="versionsStore.fieldErrors"
        :error="versionsStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'applications.versions', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionForm from '@/modules/applications/components/VersionForm.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const router = useRouter();
const versionsStore = useVersionsStore();

onMounted(() => {
  versionsStore.fetchVersion(route.params.id, route.params.versionId);
});

async function onSubmit(payload) {
  try {
    await versionsStore.updateVersion(route.params.id, route.params.versionId, payload);
    await router.push({ name: 'applications.versions', params: { id: route.params.id } });
  } catch {
    // Toast + field errors are handled by VersionForm.
  }
}
</script>
