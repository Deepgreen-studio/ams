<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'sync.configs' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
    </Teleport>

    <SyncSubnav />

    <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
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
import { RouterLink, useRouter } from 'vue-router';
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
