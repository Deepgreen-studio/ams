<template>
  <div>
    <!-- <PageHeader
      title="Edit version"
      description="Update version metadata, status, and release notes."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />
    <div
      v-if="versionsStore.loading && !versionsStore.currentVersion"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
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
// import PageHeader from '@/components/ui/PageHeader.vue';
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
  await versionsStore.updateVersion(route.params.id, route.params.versionId, payload);
  await router.push({ name: 'applications.versions', params: { id: route.params.id } });
}
</script>
