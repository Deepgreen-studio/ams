<template>
  <OrgEntityTable
    :items="departments"
    :loading="loading"
    embedded
    :columns="columns"
    empty-title="No departments"
    empty-description="Add a department to organize your company."
    @edit="$emit('edit', $event)"
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
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
  departments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];
</script>
