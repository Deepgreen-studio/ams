<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'sync.configs.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
    </Teleport>

    <SyncSubnav />

    <div
      v-if="store.loading && !store.currentConfig"
      class="h-64 animate-pulse rounded-[12px] bg-zinc-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
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
import { RouterLink, useRoute, useRouter } from 'vue-router';
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
