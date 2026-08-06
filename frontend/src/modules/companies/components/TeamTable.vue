<template>
  <OrgEntityTable
    :items="teams"
    :loading="loading"
    :columns="columns"
    empty-title="No teams"
    empty-description="Create teams under departments."
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
    <template #cell-department="{ item }">
      {{ item.department?.name || '—' }}
    </template>
  </OrgEntityTable>
</template>

<script setup>
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  teams: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'department', label: 'Department' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];
</script>
