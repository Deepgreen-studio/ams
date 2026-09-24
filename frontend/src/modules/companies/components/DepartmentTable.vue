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
    <template #cell-company="{ item }">
      <span class="text-slate-700">{{ item.company?.company_name || '—' }}</span>
    </template>
    <template #cell-note="{ item }">
      <span class="text-slate-600">{{ item.note || '—' }}</span>
    </template>
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
    <template #cell-description="{ item }">
      <span class="text-slate-600">{{ item.description || '-' }}</span>
    </template>
  </OrgEntityTable>
</template>

<script setup>
import { computed } from 'vue';
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

const props = defineProps({
  departments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  columns: { type: Array, default: null },
});

defineEmits(['edit', 'delete']);

const defaultColumns = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];

const columns = computed(() => props.columns || defaultColumns);
</script>
