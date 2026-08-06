<template>
  <div>
    <PageHeader :title="application?.name || 'Application details'" description="Application profile and configuration overview.">
      <template #actions>
        <template v-if="application">
          <RouterLink :to="{ name: 'applications.versions', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Versions
          </RouterLink>
          <RouterLink :to="{ name: 'applications.environments', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Environments
          </RouterLink>
          <RouterLink :to="{ name: 'applications.configurations', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Configurations
          </RouterLink>
          <RouterLink :to="{ name: 'applications.releases', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Releases
          </RouterLink>
          <RouterLink :to="{ name: 'applications.monitoring.crashes', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Monitoring
          </RouterLink>
          <RouterLink :to="{ name: 'applications.analytics', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Analytics
          </RouterLink>
          <RouterLink :to="{ name: 'applications.edit', params: { id: application.uuid } }" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Edit
          </RouterLink>
          <button
            v-if="application.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="applicationsStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader>

    <ApplicationSubnav v-if="application" :application-id="application.uuid" />

    <div v-if="applicationsStore.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ applicationsStore.error }}
    </div>

    <div v-if="applicationsStore.loading && !application" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <ApplicationCard v-else-if="application" :application="application" />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete application"
      :message="`Soft delete ${application?.name || 'this application'}?`"
      confirm-label="Delete"
      :loading="applicationsStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import ApplicationCard from '@/modules/applications/components/ApplicationCard.vue';
import ApplicationSubnav from '@/modules/applications/components/ApplicationSubnav.vue';
import { useApplicationsStore } from '@/modules/applications/stores/applications';

const route = useRoute();
const router = useRouter();
const applicationsStore = useApplicationsStore();
const showDelete = ref(false);

const application = computed(() => applicationsStore.currentApplication);

onMounted(() => {
  applicationsStore.fetchApplication(route.params.id);
});

async function confirmDelete() {
  await applicationsStore.deleteApplication(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'applications.index' });
}

async function restore() {
  await applicationsStore.restoreApplication(route.params.id);
  await applicationsStore.fetchApplication(route.params.id);
}
</script>
