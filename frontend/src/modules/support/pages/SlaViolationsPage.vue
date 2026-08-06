<template>
  <div>
    <PageHeader title="SLA Violation Report" description="Response and resolution breaches" />
    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
        <p class="mt-1 text-2xl font-semibold">{{ store.violationSummary?.total ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Response</p>
        <p class="mt-1 text-2xl font-semibold">{{ store.violationSummary?.response ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Resolution</p>
        <p class="mt-1 text-2xl font-semibold">{{ store.violationSummary?.resolution ?? 0 }}</p>
      </div>
    </div>

    <div class="mb-4">
      <select v-model="metric" class="input w-auto" @change="reload">
        <option value="">All metrics</option>
        <option value="response">Response</option>
        <option value="resolution">Resolution</option>
      </select>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Ticket</th>
            <th class="px-4 py-3">SLA</th>
            <th class="px-4 py-3">Response breach</th>
            <th class="px-4 py-3">Resolution breach</th>
            <th class="px-4 py-3">Assignee</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.violations.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No violations found.</td>
          </tr>
          <tr v-for="ticket in store.violations" :key="ticket.uuid">
            <td class="px-4 py-3">
              <RouterLink
                :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ ticket.ticket_number }}
              </RouterLink>
              <p class="text-xs text-slate-500">{{ ticket.subject }}</p>
            </td>
            <td class="px-4 py-3">
              <SlaStatusBadge :status="ticket.sla_status" :label="ticket.sla_status_label" />
            </td>
            <td class="px-4 py-3">{{ formatDate(ticket.response_breached_at) }}</td>
            <td class="px-4 py-3">{{ formatDate(ticket.resolution_breached_at) }}</td>
            <td class="px-4 py-3">{{ ticket.assignee?.full_name || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const metric = ref('');

onMounted(reload);

function reload() {
  store.fetchViolations({ metric: metric.value || undefined });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>

<style scoped>
.input {
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
