<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <div v-if="application" class="flex flex-wrap items-center justify-end gap-2">
        <RouterLink
          :to="{ name: 'applications.edit', params: { id: application.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          v-if="application.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="applicationsStore.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
      </div>
    </Teleport>

    <ApplicationSubnav v-if="application" :application-id="application.uuid" />

    <div
      v-if="applicationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ applicationsStore.error }}
    </div>

    <div
      v-if="applicationsStore.loading && !application"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="application">
      <ApplicationCard :application="application" />
    </div>

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
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
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
