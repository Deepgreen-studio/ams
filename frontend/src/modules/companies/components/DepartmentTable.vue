<template>
  <OrgEntityTable
    :items="departments"
    :loading="loading"
    :columns="columns"
    empty-title="No departments"
    empty-description="Add a department to organize your company."
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
  </OrgEntityTable>
</template>

<script setup>
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  departments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];
</script>
