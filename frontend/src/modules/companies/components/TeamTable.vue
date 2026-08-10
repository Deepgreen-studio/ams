<template>
  <OrgEntityTable
    :items="teams"
    :loading="loading"
    :embedded="embedded"
    :columns="columns"
    empty-title="No teams"
    empty-description="Create teams under departments."
    @edit="$emit('edit', $event)"
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
    <template #cell-department="{ item }">
      <span class="text-slate-600">{{ item.department?.name || '-' }}</span>
    </template>
    <template #cell-description="{ item }">
      <span class="text-slate-600">{{ item.description || '-' }}</span>
    </template>
  </OrgEntityTable>
</template>

<script setup>
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  teams: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'department', label: 'Department' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];
</script>
