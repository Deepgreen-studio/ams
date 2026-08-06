<template>
  <div>
    <PageHeader
      title="Create mapping"
      description="Build a reusable field mapping profile for an integration."
    />
    <MappingSubnav />
    <div class="rounded-xl border border-slate-200 bg-white p-6">
      <MappingForm
        :loading="store.saving"
        :error="store.error || ''"
        :catalogs="store.catalogs"
        submit-label="Create mapping"
        @submit="onSubmit"
        @cancel="router.push({ name: 'mappings.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import MappingForm from '@/modules/mappings/components/MappingForm.vue';
import MappingSubnav from '@/modules/mappings/components/MappingSubnav.vue';
import { useMappingsStore } from '@/modules/mappings/stores/mappings';

const router = useRouter();
const store = useMappingsStore();

onMounted(() => store.fetchCatalogs());

async function onSubmit(payload) {
  if (!payload) return;
  const mapping = await store.createMapping(payload);
  await router.push({ name: 'mappings.show', params: { id: mapping.uuid } });
}
</script>
