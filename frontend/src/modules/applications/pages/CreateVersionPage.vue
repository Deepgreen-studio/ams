<template>
  <div>
    <!-- <PageHeader
      title="Create version"
      description="Register a new semantic version for this application."
    /> -->
    <ApplicationSubnav :application-id="route.params.id" />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import VersionForm from '@/modules/applications/components/VersionForm.vue';
import { useVersionsStore } from '@/modules/applications/stores/versions';

const route = useRoute();
const router = useRouter();
const versionsStore = useVersionsStore();

async function onSubmit(payload) {
  await versionsStore.createVersion(route.params.id, payload);
  await router.push({ name: 'applications.versions', params: { id: route.params.id } });
}
</script>
