<template>
  <div class="rounded-[12px] bg-white p-6 sm:p-8">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-full px-3 py-1.5 text-sm font-medium"
        :class="active === tab.id ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-slate-600 hover:bg-zinc-200'"
        @click="active = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="mt-6 h-32 animate-pulse rounded-[12px] bg-slate-100" />

    <div v-else-if="active === 'overview'" class="mt-6 space-y-4">
      <p class="text-sm text-slate-600">{{ consoleData?.model?.ownership_note }}</p>
      <dl class="grid gap-3 sm:grid-cols-2">
        <div v-for="item in overviewItems" :key="item.label" class="rounded-[12px] bg-slate-50 px-4 py-3">
          <dt class="text-xs text-slate-500">{{ item.label }}</dt>
          <dd class="mt-1 text-sm font-medium text-slate-900">{{ item.value }}</dd>
        </div>
      </dl>
      <div class="grid gap-3 sm:grid-cols-3">
        <div v-for="stat in stats" :key="stat.label" class="rounded-[12px] border border-slate-100 px-4 py-3">
          <p class="text-xs text-slate-500">{{ stat.label }}</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <div v-else-if="active === 'support'" class="mt-6 space-y-6">
      <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <p class="text-xs text-slate-500">Open tickets</p>
          <p class="mt-1 text-lg font-semibold">{{ consoleData?.counts?.open_tickets ?? 0 }}</p>
        </div>
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <p class="text-xs text-slate-500">Critical issues</p>
          <p class="mt-1 text-lg font-semibold">{{ consoleData?.counts?.critical_issues ?? 0 }}</p>
        </div>
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <p class="text-xs text-slate-500">SLA breaches</p>
          <p class="mt-1 text-lg font-semibold">{{ consoleData?.counts?.sla_breaches ?? 0 }}</p>
        </div>
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <p class="text-xs text-slate-500">Assigned teams</p>
          <p class="mt-1 text-sm font-medium">{{ teamLabel }}</p>
        </div>
      </div>
      <div>
        <h4 class="text-sm font-semibold text-slate-900">Open tickets</h4>
        <p v-if="!(consoleData?.support?.open_tickets || []).length" class="mt-2 text-sm text-slate-500">None</p>
        <ul v-else class="mt-2 divide-y divide-slate-100">
          <li v-for="ticket in consoleData.support.open_tickets" :key="ticket.uuid" class="flex items-center justify-between gap-3 py-2 text-sm">
            <RouterLink :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }" class="font-medium text-brand-700">
              {{ ticket.ticket_number }} · {{ ticket.subject }}
            </RouterLink>
            <span class="text-slate-500">{{ ticket.priority }} · {{ ticket.sla_status || ticket.status }}</span>
          </li>
        </ul>
      </div>
      <div>
        <h4 class="text-sm font-semibold text-slate-900">Critical issues and bug reports</h4>
        <p v-if="!(consoleData?.support?.critical_issues || []).length" class="mt-2 text-sm text-slate-500">None</p>
        <ul v-else class="mt-2 divide-y divide-slate-100">
          <li v-for="ticket in consoleData.support.critical_issues" :key="`critical-${ticket.uuid}`" class="flex items-center justify-between gap-3 py-2 text-sm">
            <RouterLink :to="{ name: 'support.tickets.show', params: { id: ticket.uuid } }" class="font-medium text-brand-700">
              {{ ticket.ticket_number }} · {{ ticket.subject }}
            </RouterLink>
            <span class="text-slate-500">{{ ticket.priority }} · {{ ticket.status }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div v-else-if="active === 'health'" class="mt-6">
      <p class="text-sm text-slate-500">
        Health is read from application monitoring. Empty rows mean no connector signal has been recorded.
      </p>
      <div v-if="!(consoleData?.health || []).length" class="mt-4 text-sm text-slate-500">
        No application assignments yet.
      </div>
      <div v-else class="mt-4 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
              <th class="px-3 py-2">Assignment</th>
              <th class="px-3 py-2">Application</th>
              <th class="px-3 py-2">Environment</th>
              <th class="px-3 py-2">Version</th>
              <th class="px-3 py-2">Health</th>
              <th class="px-3 py-2">API errors</th>
              <th class="px-3 py-2">Crashes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in consoleData.health" :key="row.assignment_uuid" class="border-t border-slate-100">
              <td class="px-3 py-2">{{ row.assignment_number || '—' }}</td>
              <td class="px-3 py-2">{{ row.application || '—' }}</td>
              <td class="px-3 py-2">{{ row.environment || '—' }}</td>
              <td class="px-3 py-2">{{ row.version || '—' }}</td>
              <td class="px-3 py-2">{{ row.health_score ?? 'No signal' }}</td>
              <td class="px-3 py-2">{{ formatRate(row.api_error_rate) }}</td>
              <td class="px-3 py-2">{{ formatRate(row.crash_rate) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else-if="active === 'privacy'" class="mt-6 space-y-4">
      <dl class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <dt class="text-xs text-slate-500">Legal basis</dt>
          <dd class="mt-1 text-sm font-medium">{{ consoleData?.privacy?.legal_basis_label || 'Not specified' }}</dd>
        </div>
        <div class="rounded-[12px] bg-slate-50 px-4 py-3">
          <dt class="text-xs text-slate-500">Retain until</dt>
          <dd class="mt-1 text-sm font-medium">{{ consoleData?.privacy?.retention_until || 'Not set' }}</dd>
        </div>
        <div class="rounded-[12px] bg-slate-50 px-4 py-3 sm:col-span-2">
          <dt class="text-xs text-slate-500">Processing purpose</dt>
          <dd class="mt-1 text-sm font-medium whitespace-pre-wrap">{{ consoleData?.privacy?.processing_purpose || 'Not specified' }}</dd>
        </div>
      </dl>
      <div>
        <h4 class="text-sm font-semibold text-slate-900">Privacy contact</h4>
        <p v-if="!(consoleData?.privacy?.privacy_contacts || []).length" class="mt-2 text-sm text-slate-500">
          No Compliance / Privacy contact is recorded.
        </p>
        <ul v-else class="mt-2 divide-y divide-slate-100">
          <li v-for="contact in consoleData.privacy.privacy_contacts" :key="contact.uuid" class="py-2 text-sm">
            <RouterLink
              :to="{ name: 'customers.contacts.show', params: { id: customerId, contactId: contact.uuid } }"
              class="font-medium text-brand-700"
            >
              {{ contact.name || 'Unnamed contact' }}
            </RouterLink>
            <p class="text-slate-500">
              {{ contact.email || 'No email' }}<span v-if="contact.phone"> · {{ contact.phone }}</span>
              <span v-if="contact.position"> · {{ contact.position }}</span>
            </p>
          </li>
        </ul>
      </div>
      <div>
        <h4 class="text-sm font-semibold text-slate-900">Consent and preferences</h4>
        <p v-if="!(consoleData?.privacy?.consents || []).length" class="mt-2 text-sm text-slate-500">
          No consent records are linked to this customer.
        </p>
        <ul v-else class="mt-2 divide-y divide-slate-100">
          <li v-for="consent in consoleData.privacy.consents" :key="consent.uuid" class="flex items-center justify-between gap-3 py-2 text-sm">
            <RouterLink :to="{ name: 'compliance.consents.show', params: { id: consent.uuid } }" class="font-medium text-brand-700">
              {{ consent.consent_type || 'Consent' }}
            </RouterLink>
            <span class="text-slate-500">{{ consent.status_label || consent.status }}</span>
          </li>
        </ul>
      </div>
      <div v-if="consoleData?.privacy?.anonymized_at" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Personal data was anonymized.
      </div>
      <div v-else-if="canAnonymize" class="flex justify-end">
        <button
          type="button"
          class="rounded-[12px] border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
          @click="$emit('anonymize')"
        >
          Anonymize personal data
        </button>
      </div>
      <div>
        <h4 class="text-sm font-semibold text-slate-900">Privacy requests</h4>
        <p v-if="!(consoleData?.privacy?.requests || []).length" class="mt-2 text-sm text-slate-500">No privacy requests linked to this customer.</p>
        <ul v-else class="mt-2 divide-y divide-slate-100">
          <li v-for="request in consoleData.privacy.requests" :key="request.uuid" class="flex items-center justify-between py-2 text-sm">
            <RouterLink :to="{ name: 'compliance.privacy.show', params: { id: request.uuid } }" class="font-medium text-brand-700">
              {{ request.request_number || request.uuid }}
            </RouterLink>
            <span class="text-slate-500">{{ request.status }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div v-else class="mt-6">
      <p v-if="!(consoleData?.activity || []).length" class="text-sm text-slate-500">No activity recorded yet.</p>
      <ul v-else class="divide-y divide-slate-100">
        <li v-for="item in consoleData.activity" :key="item.id" class="py-3 text-sm">
          <p class="font-medium text-slate-900">{{ item.description }}</p>
          <p class="text-xs text-slate-500">{{ item.causer || 'System' }} · {{ formatDate(item.created_at) }}</p>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { formatDate } from '@/utils/formatters';
import { customerService } from '@/modules/customers/services/customerService';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
  customerId: { type: String, required: true },
  customer: { type: Object, default: null },
});

defineEmits(['anonymize']);

const { can } = usePermissions();
const canAnonymize = computed(() => can('customers.anonymize'));
const active = ref('overview');
const loading = ref(false);
const consoleData = ref(null);

const tabs = [
  { id: 'overview', label: 'Overview' },
  { id: 'support', label: 'Support' },
  { id: 'health', label: 'Application health' },
  { id: 'privacy', label: 'Privacy' },
  { id: 'activity', label: 'Activity' },
];

const overviewItems = computed(() => [
  { label: 'Customer ID', value: props.customer?.customer_number || '—' },
  { label: 'Business reference', value: props.customer?.reference || '—' },
  { label: 'Type', value: props.customer?.customer_type_label || props.customer?.customer_type || '—' },
  { label: 'Organization', value: props.customer?.is_organization ? (props.customer?.company_name || '—') : 'Individual' },
  { label: 'Registration / Tax ID', value: props.customer?.registration_number || '—' },
  { label: 'Primary contact', value: props.customer?.primary_contact_name || '—' },
  { label: 'Timezone', value: props.customer?.timezone || '—' },
  { label: 'Notes', value: props.customer?.notes || '—' },
]);

const stats = computed(() => [
  { label: 'Contacts', value: consoleData.value?.counts?.contacts ?? 0 },
  { label: 'Applications', value: consoleData.value?.counts?.applications ?? 0 },
  { label: 'Subscriptions', value: consoleData.value?.counts?.subscriptions ?? 0 },
  { label: 'Licenses', value: consoleData.value?.counts?.licenses ?? 0 },
  { label: 'Documents', value: consoleData.value?.counts?.documents ?? 0 },
  { label: 'Communications', value: consoleData.value?.counts?.communications ?? 0 },
]);

const teamLabel = computed(() => {
  const teams = consoleData.value?.support?.teams || [];
  return teams.length ? teams.join(', ') : 'None';
});

watch(() => props.customerId, load);

onMounted(load);

async function load() {
  if (!props.customerId) return;
  loading.value = true;
  try {
    const { data } = await customerService.console(props.customerId);
    consoleData.value = data.data?.console ?? null;
  } catch {
    consoleData.value = null;
  } finally {
    loading.value = false;
  }
}

function formatRate(value) {
  if (value === null || value === undefined || value === '') return '—';
  return `${value}`;
}

defineExpose({ reload: load });
</script>
