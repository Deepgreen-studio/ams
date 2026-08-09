<template>
  <div>
    <!-- <PageHeader
      title="Consent audit view"
      description="Audit trail of consent changes with IP, device, source, and actor metadata."
    /> -->
    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
      <form class="flex flex-col gap-3 lg:flex-row lg:items-end" @submit.prevent="onSearch">
        <div class="flex-1">
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
          <input
            v-model="filters.search"
            type="search"
            placeholder="Subject, IP, comments..."
            class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm"
          />
        </div>
        <div class="w-full lg:w-44">
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Action</label>
          <select v-model="filters.action" class="h-12 w-full rounded-[12px] border border-slate-300 px-3 text-sm">
            <option value="">All</option>
            <option value="granted">Granted</option>
            <option value="withdrawn">Withdrawn</option>
            <option value="updated">Updated</option>
            <option value="version_changed">Version changed</option>
            <option value="created">Created</option>
          </select>
        </div>
        <button type="submit" class="h-12 rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700">
          Search
        </button>
      </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.history.length"
        title="No audit events"
        description="Consent history events will appear here."
      />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Subject</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Type</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">IP / Device</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Actor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in store.history" :key="item.uuid" class="hover:bg-slate-50/80">
              <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
              <td class="px-4 py-3 font-medium text-slate-900">{{ item.action_label || item.action }}</td>
              <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
                {{ item.user_consent?.subject_email || '—' }}
              </td>
              <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
                {{ item.consent_type?.name || '—' }}
              </td>
              <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
                {{ item.ip_address || '—' }} · {{ item.device || '—' }}
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.actor?.full_name || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-4">
      <Pagination
        :meta="store.historyMeta"
        :loading="store.loading"
        @change="(page) => store.fetchHistory({ ...filters, page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useConsentStore();
const filters = reactive({
  search: '',
  action: '',
  per_page: 20,
  page: 1,
});

onMounted(() => {
  store.fetchHistory(filters);
});

function onSearch() {
  filters.page = 1;
  store.fetchHistory(filters);
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
