<template>
  <OrgEntityTable
    :items="locations"
    :loading="loading"
    :embedded="embedded"
    :columns="columns"
    empty-title="No locations"
    empty-description="Add office locations for this organization."
    @edit="$emit('edit', $event)"
    @delete="$emit('delete', $event)"
  >
    <template #cell-status="{ item }">
      <StatusBadge :status="item.status" />
    </template>
    <template #cell-city="{ item }">
      <span class="text-slate-600">
        {{ [item.city, item.country].filter(Boolean).join(', ') || '-' }}
      </span>
    </template>
    <template #cell-phone="{ item }">
      <span class="text-slate-600">{{ item.phone || '-' }}</span>
    </template>
    <template #cell-email="{ item }">
      <span class="text-slate-600">{{ item.email || '-' }}</span>
    </template>
  </OrgEntityTable>
</template>

<script setup>
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

defineProps({
  locations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  embedded: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);

const columns = [
  { key: 'branch_name', label: 'Branch' },
  { key: 'city', label: 'Location' },
  { key: 'phone', label: 'Phone' },
  { key: 'email', label: 'Email' },
  { key: 'status', label: 'Status' },
];
</script>
