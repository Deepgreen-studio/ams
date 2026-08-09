<template>
  <div>
    <!-- <PageHeader
      title="Create sync config"
      description="Define how AMS imports or exports data for an integration."
    /> -->
    <SyncSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <SyncConfigForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || formError"
        submit-label="Create config"
        @submit="onSubmit"
        @cancel="router.push({ name: 'sync.configs' })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SyncConfigForm from '@/modules/sync/components/SyncConfigForm.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const router = useRouter();
const store = useSyncStore();
const formError = ref('');

async function onSubmit(payload) {
  formError.value = '';
  if (!payload) {
    formError.value = 'Sample records must be valid JSON.';
    return;
  }
  const config = await store.createConfig(payload);
  await router.push({ name: 'sync.configs.show', params: { id: config.uuid } });
}
</script>
