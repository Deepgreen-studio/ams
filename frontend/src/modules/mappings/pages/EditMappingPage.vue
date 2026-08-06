<template>
  <div>
    <PageHeader
      title="Edit mapping"
      description="Update field mappings, transforms, and validation rules."
    />
    <MappingSubnav />
    <div
      v-if="store.loading && !store.currentMapping"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <MappingForm
        :initial="store.currentMapping || {}"
        :loading="store.saving"
        :error="store.error || ''"
        :catalogs="store.catalogs"
        hide-company
        submit-label="Save mapping"
        @submit="onSubmit"
        @cancel="router.push({ name: 'mappings.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import MappingForm from '@/modules/mappings/components/MappingForm.vue';
import MappingSubnav from '@/modules/mappings/components/MappingSubnav.vue';
import { useMappingsStore } from '@/modules/mappings/stores/mappings';

const route = useRoute();
const router = useRouter();
const store = useMappingsStore();

onMounted(async () => {
  await Promise.all([store.fetchCatalogs(), store.fetchMapping(route.params.id)]);
});

async function onSubmit(payload) {
  if (!payload) return;
  await store.updateMapping(route.params.id, payload);
  await router.push({ name: 'mappings.show', params: { id: route.params.id } });
}
</script>
