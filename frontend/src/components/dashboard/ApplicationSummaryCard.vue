<template>
  <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-100">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h2 class="text-base font-semibold text-zinc-900">Application summary</h2>
      <div class="flex flex-wrap gap-2">
        <select v-model="filters.app" class="dash-select">
          <option value="">Application</option>
          <option v-for="opt in appOptions" :key="opt" :value="opt">{{ opt }}</option>
        </select>
        <select v-model="filters.owner" class="dash-select">
          <option value="">Owner</option>
          <option v-for="opt in ownerOptions" :key="opt" :value="opt">{{ opt }}</option>
        </select>
        <select v-model="filters.status" class="dash-select">
          <option value="">Status</option>
          <option v-for="opt in statusOptions" :key="opt" :value="opt">{{ opt }}</option>
        </select>
      </div>
    </div>

    <div v-if="!rows.length" class="py-10 text-center text-sm text-zinc-500">
      No applications found.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead>
          <tr class="border-b border-zinc-100 text-xs font-medium uppercase tracking-wide text-zinc-400">
            <th class="pb-3 pr-4 font-medium">Name</th>
            <th class="pb-3 pr-4 font-medium">Owner</th>
            <th class="pb-3 pr-4 font-medium">Due date</th>
            <th class="pb-3 pr-4 font-medium">Status</th>
            <th class="pb-3 font-medium">Progress</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in filteredRows"
            :key="row.uuid"
            class="border-b border-zinc-50 last:border-0"
          >
            <td class="py-3.5 pr-4 font-medium text-zinc-900">
              <RouterLink
                :to="{ name: 'applications.show', params: { id: row.uuid } }"
                class="hover:text-brand-600"
              >
                {{ row.name }}
              </RouterLink>
            </td>
            <td class="py-3.5 pr-4">
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold text-white"
                  :style="{ backgroundColor: ownerColor(row.owner?.full_name) }"
                >
                  {{ row.owner?.initials || '—' }}
                </span>
                <span class="text-zinc-600">{{ row.owner?.full_name || 'Unassigned' }}</span>
              </div>
            </td>
            <td class="py-3.5 pr-4 text-zinc-600">{{ formatDate(row.due_date) }}</td>
            <td class="py-3.5 pr-4">
              <span
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusClass(row.status)"
              >
                {{ row.status_label || row.status }}
              </span>
            </td>
            <td class="py-3.5">
              <div class="relative inline-flex h-10 w-10 items-center justify-center">
                <svg class="h-10 w-10 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                  <circle
                    cx="18"
                    cy="18"
                    r="14"
                    fill="none"
                    stroke="#f4f4f5"
                    stroke-width="3"
                  />
                  <circle
                    cx="18"
                    cy="18"
                    r="14"
                    fill="none"
                    :stroke="progressColor(row.progress)"
                    stroke-width="3"
                    stroke-linecap="round"
                    :stroke-dasharray="`${(row.progress || 0) * 0.88} 88`"
                  />
                </svg>
                <span class="absolute text-[10px] font-semibold text-zinc-700">{{ row.progress || 0 }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
});

const filters = reactive({
  app: '',
  owner: '',
  status: '',
});

const appOptions = computed(() => [...new Set(props.rows.map((r) => r.name).filter(Boolean))]);
const ownerOptions = computed(() => [
  ...new Set(props.rows.map((r) => r.owner?.full_name).filter(Boolean)),
]);
const statusOptions = computed(() => [
  ...new Set(props.rows.map((r) => r.status_label || r.status).filter(Boolean)),
]);

const filteredRows = computed(() =>
  props.rows.filter((row) => {
    if (filters.app && row.name !== filters.app) return false;
    if (filters.owner && row.owner?.full_name !== filters.owner) return false;
    const label = row.status_label || row.status;
    if (filters.status && label !== filters.status) return false;
    return true;
  }),
);

const palette = ['#7c3aed', '#ea580c', '#2563eb', '#db2777', '#0d9488', '#ca8a04', '#4f46e5'];

function ownerColor(name) {
  const raw = String(name || 'U');
  let hash = 0;
  for (let i = 0; i < raw.length; i += 1) {
    hash = (hash + raw.charCodeAt(i) * (i + 1)) % 997;
  }
  return palette[hash % palette.length];
}

function formatDate(value) {
  if (!value) return '—';
  try {
    return new Date(value).toLocaleDateString(undefined, {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return value;
  }
}

function statusClass(status) {
  const map = {
    active: 'bg-emerald-50 text-emerald-700',
    draft: 'bg-zinc-100 text-zinc-600',
    inactive: 'bg-amber-50 text-amber-700',
    archived: 'bg-sky-50 text-sky-700',
  };
  return map[String(status || '').toLowerCase()] || 'bg-zinc-100 text-zinc-600';
}

function progressColor(progress) {
  if (progress >= 80) return '#10b981';
  if (progress >= 50) return '#f59e0b';
  return '#ff5c00';
}
</script>

<style scoped>
.dash-select {
  appearance: auto;
  border-radius: 9999px;
  border: 1px solid #e4e4e7;
  background: #fff;
  padding: 0.35rem 0.85rem;
  font-size: 0.75rem;
  color: #52525b;
  outline: none;
}
.dash-select:focus {
  border-color: #ff5c00;
  box-shadow: none;
}
</style>
