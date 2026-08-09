<template>
  <div>
    <PageHeader title="Edit sync config" description="Update synchronization settings." />
    <SyncSubnav />
    <div
      v-if="store.loading && !store.currentConfig"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <SyncConfigForm
        :initial="store.currentConfig || {}"
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || formError"
        submit-label="Save changes"
        hide-company
        @submit="onSubmit"
        @cancel="router.push({ name: 'sync.configs.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SyncConfigForm from '@/modules/sync/components/SyncConfigForm.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const route = useRoute();
const router = useRouter();
const store = useSyncStore();
const formError = ref('');

onMounted(() => store.fetchConfig(route.params.id));

async function onSubmit(payload) {
  formError.value = '';
  if (!payload) {
    formError.value = 'Sample records must be valid JSON.';
    return;
  }
  await store.updateConfig(route.params.id, payload);
  await router.push({ name: 'sync.configs.show', params: { id: route.params.id } });
}
</script>
