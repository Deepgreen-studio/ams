<template>
  <OrgEntityTable
    :items="locations"
    :loading="loading"
    :columns="columns"
    empty-title="No locations"
    empty-description="Add office locations for this organization."
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
    <template #cell-city="{ item }">
      {{ [item.city, item.country].filter(Boolean).join(', ') || '—' }}
    </template>
  </OrgEntityTable>
</template>

<script setup>
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  locations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

const columns = [
  { key: 'branch_name', label: 'Branch' },
  { key: 'city', label: 'Location' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' },
];
</script>
