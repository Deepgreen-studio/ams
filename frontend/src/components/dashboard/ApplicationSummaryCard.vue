<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-zinc-100">
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

    <div class="overflow-x-auto">
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
            :key="row.id"
            class="border-b border-zinc-50 last:border-0"
          >
            <td class="py-3.5 pr-4 font-medium text-zinc-900">{{ row.name }}</td>
            <td class="py-3.5 pr-4">
              <div class="flex items-center gap-2">
                <span
                  class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold text-white"
                  :style="{ backgroundColor: row.ownerColor }"
                >
                  {{ row.ownerInitials }}
                </span>
                <span class="text-zinc-600">{{ row.owner }}</span>
              </div>
            </td>
            <td class="py-3.5 pr-4 text-zinc-600">{{ row.dueDate }}</td>
            <td class="py-3.5 pr-4">
              <span
                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                :class="statusClass(row.status)"
              >
                {{ row.status }}
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
                    :stroke-dasharray="`${row.progress * 0.88} 88`"
                  />
                </svg>
                <span class="absolute text-[10px] font-semibold text-zinc-700">{{ row.progress }}%</span>
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

const filters = reactive({
  app: '',
  owner: '',
  status: '',
});

const rows = [
  {
    id: 1,
    name: 'AMS Mobile Release',
    owner: 'Sarah Chen',
    ownerInitials: 'SC',
    ownerColor: '#7c3aed',
    dueDate: '15 May 2026',
    status: 'Completed',
    progress: 100,
  },
  {
    id: 2,
    name: 'Customer Portal Revamp',
    owner: 'James Park',
    ownerInitials: 'JP',
    ownerColor: '#ea580c',
    dueDate: '20 May 2026',
    status: 'Delayed',
    progress: 45,
  },
  {
    id: 3,
    name: 'Integration Hub API',
    owner: 'Mia Lopez',
    ownerInitials: 'ML',
    ownerColor: '#2563eb',
    dueDate: '25 May 2026',
    status: 'At risk',
    progress: 32,
  },
  {
    id: 4,
    name: 'Compliance Audit Pack',
    owner: 'Alex Melan',
    ownerInitials: 'AM',
    ownerColor: '#db2777',
    dueDate: '30 May 2026',
    status: 'On going',
    progress: 68,
  },
  {
    id: 5,
    name: 'Support Knowledge Base',
    owner: 'Ken Ortiz',
    ownerInitials: 'KO',
    ownerColor: '#0d9488',
    dueDate: '02 Jun 2026',
    status: 'On going',
    progress: 54,
  },
];

const appOptions = computed(() => [...new Set(rows.map((r) => r.name))]);
const ownerOptions = computed(() => [...new Set(rows.map((r) => r.owner))]);
const statusOptions = ['Completed', 'Delayed', 'At risk', 'On going'];

const filteredRows = computed(() =>
  rows.filter((row) => {
    if (filters.app && row.name !== filters.app) return false;
    if (filters.owner && row.owner !== filters.owner) return false;
    if (filters.status && row.status !== filters.status) return false;
    return true;
  }),
);

function statusClass(status) {
  const map = {
    Completed: 'bg-emerald-50 text-emerald-700',
    Delayed: 'bg-amber-50 text-amber-700',
    'At risk': 'bg-rose-50 text-rose-700',
    'On going': 'bg-orange-50 text-orange-700',
  };
  return map[status] || 'bg-zinc-100 text-zinc-600';
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
  box-shadow: 0 0 0 3px rgba(255, 92, 0, 0.12);
}
</style>
