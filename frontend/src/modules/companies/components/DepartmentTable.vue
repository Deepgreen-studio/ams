<template>
  <OrgEntityTable
    :items="departments"
    :loading="loading"
    embedded
    show-view
    :columns="columns"
    :empty-title="emptyTitle"
    :empty-description="emptyDescription"
    :sort-by="sortBy"
    :sort-dir="sortDir"
    @sort="$emit('sort', $event)"
    @view="$emit('view', $event)"
    @edit="$emit('edit', $event)"
    @delete="$emit('delete', $event)"
  >
    <template #cell-name="{ item }">
      <RouterLink
        :to="{ name: 'departments.show', params: { id: item.uuid } }"
        class="font-semibold text-slate-900 hover:text-brand-700"
      >
        {{ item.department_name || item.name || '—' }}
      </RouterLink>
    </template>
    <template #cell-company="{ item }">
      <span class="text-slate-700">{{ item.company?.company_name || '—' }}</span>
    </template>
    <template #cell-team="{ item }">
      <span class="text-slate-700">{{ teamNames(item) }}</span>
    </template>
    <template #cell-created_at="{ item }">
      <span class="text-slate-600">{{ formatDate(item.created_at) || '—' }}</span>
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
import { RouterLink } from 'vue-router';
import OrgEntityTable from '@/modules/companies/components/OrgEntityTable.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { formatDate } from '@/utils/formatters';

const props = defineProps({
  departments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  columns: { type: Array, default: null },
  emptyTitle: { type: String, default: 'No departments' },
  emptyDescription: { type: String, default: 'Add a department to organize your company.' },
  sortBy: { type: String, default: '' },
  sortDir: { type: String, default: 'asc' },
});

defineEmits(['view', 'edit', 'delete', 'sort']);

const defaultColumns = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' },
];

const columns = computed(() => props.columns || defaultColumns);

function teamNames(item) {
  const names = (item.teams || []).map((team) => team.name).filter(Boolean);
  return names.length ? names.join(', ') : '—';
}
</script>
