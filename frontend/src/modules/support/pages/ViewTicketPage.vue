<template>
  <div>
    <PageHeader
      :title="ticket?.ticket_number || 'Ticket details'"
      :description="ticket?.subject || 'Support ticket overview'"
    >
      <template #actions>
        <template v-if="ticket">
          <button
            v-if="ticket.status === 'closed' || ticket.status === 'cancelled'"
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="store.saving"
            @click="reopenTicket"
          >
            Reopen
          </button>
          <button
            v-else-if="ticket.status !== 'closed'"
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="store.saving"
            @click="closeTicket"
          >
            Close ticket
          </button>
          <button
            v-if="ticket.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="restoreTicket"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
        </template>
      </template>
    </PageHeader>

    <SupportSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading && !ticket" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="ticket" class="space-y-6">
      <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
            <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            <TicketCategoryBadge :category="ticket.category" :label="ticket.category_label" />
            <span
              v-if="ticket.source"
              class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"
            >
              {{ ticket.source_label || ticket.source }}
            </span>
          </div>
          <h2 class="text-lg font-semibold text-slate-900">{{ ticket.subject }}</h2>

          <div class="mt-4">
            <TicketDescriptionPanel
              :description="ticket.description || ''"
              :source="ticket.source || ''"
              :has-linked-customer="Boolean(ticket.customer?.display_name)"
            />
          </div>

          <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Change status</h3>
            <div class="flex flex-col gap-3 sm:flex-row">
              <select v-model="transitionStatus" class="input flex-1">
                <option value="" disabled>Select next status</option>
                <option
                  v-for="option in ticket.allowed_transitions || []"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <button
                type="button"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                :disabled="store.saving || !transitionStatus"
                @click="applyTransition"
              >
                Update status
              </button>
            </div>
            <textarea
              v-model="transitionComments"
              rows="2"
              class="input mt-3"
              placeholder="Optional transition comment"
            />
          </div>
        </div>

        <div class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between gap-2">
              <h3 class="text-sm font-semibold text-slate-900">SLA Timers</h3>
              <SlaStatusBadge :status="ticket.sla_status" :label="ticket.sla_status_label" />
            </div>
            <p class="mb-3 text-xs text-slate-500">
              {{ ticket.sla_policy?.name || 'No active policy' }}
              <span v-if="ticket.escalation_level_label"> · Escalation: {{ ticket.escalation_level_label }}</span>
            </p>
            <div class="grid gap-2 sm:grid-cols-2">
              <SlaCountdown
                label="Response"
                :due-at="ticket.first_response_due_at"
                :completed-at="ticket.first_response_at"
              />
              <SlaCountdown
                label="Resolution"
                :due-at="ticket.resolution_due_at"
                :completed-at="ticket.resolved_at"
              />
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-900">Details</h3>
            <dl class="mt-4 space-y-3 text-sm">
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Ticket number</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.ticket_number }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Company</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.company?.company_name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Customer</dt>
                <dd class="mt-1 text-slate-900">
                  <template v-if="ticket.customer?.display_name">
                    {{ ticket.customer.display_name }}
                  </template>
                  <template v-else-if="ingestContact.name || ingestContact.email || ingestContact.from">
                    <span class="font-medium">{{ ingestContact.name || ingestContact.from || '—' }}</span>
                    <span
                      v-if="ingestContact.email"
                      class="mt-0.5 block text-xs text-slate-500"
                    >{{ ingestContact.email }}</span>
                    <span
                      v-if="ingestContact.from && ingestContact.name"
                      class="mt-0.5 block text-xs text-slate-500"
                    >{{ ingestContact.from }}</span>
                  </template>
                  <template v-else>—</template>
                </dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Application</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.application?.name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Department / Team</dt>
                <dd class="mt-1 text-slate-900">
                  {{ ticket.department?.name || '—' }} / {{ ticket.team?.name || '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Assignment</dt>
                <dd class="mt-1 text-slate-900">
                  {{ ticket.assignment_type_label || 'Unassigned' }}
                  <span v-if="ticket.assignee"> · {{ ticket.assignee.full_name }}</span>
                </dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Source</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.source_label || ticket.source }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Created</dt>
                <dd class="mt-1 text-slate-900">{{ formatDate(ticket.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Closed</dt>
                <dd class="mt-1 text-slate-900">{{ formatDate(ticket.closed_at) }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="mb-4 text-sm font-semibold text-slate-900">Assign ticket</h3>
            <AssignmentPanel
              :company-id="ticket.company?.uuid || ''"
              :agents="store.agents"
              :loading="store.saving"
              submit-label="Apply assignment"
              @submit="onAssign"
            />
          </div>
        </div>
      </div>

      <TicketConversationPanel :ticket-id="ticket.uuid || String(route.params.id)" />

      <TicketStatusTimeline :history="store.timeline" :loading="timelineLoading" />

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="mb-4 text-sm font-semibold text-slate-900">Update ticket</h3>
        <TicketForm
          :initial="ticket"
          :loading="store.saving"
          :errors="store.fieldErrors"
          :error="store.error || ''"
          submit-label="Save changes"
          @submit="onUpdate"
          @cancel="router.push({ name: 'support.tickets.index' })"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive ticket"
      :message="`Archive ${ticket?.ticket_number || 'this ticket'}? It can be restored later.`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="showArchive = false"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import AssignmentPanel from '@/modules/support/components/AssignmentPanel.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SlaCountdown from '@/modules/support/components/SlaCountdown.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketCategoryBadge from '@/modules/support/components/TicketCategoryBadge.vue';
import TicketConversationPanel from '@/modules/support/components/TicketConversationPanel.vue';
import TicketDescriptionPanel from '@/modules/support/components/TicketDescriptionPanel.vue';
import TicketForm from '@/modules/support/components/TicketForm.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';
import TicketStatusTimeline from '@/modules/support/components/TicketStatusTimeline.vue';
import { parseTicketDescription } from '@/modules/support/utils/parseTicketDescription';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const route = useRoute();
const router = useRouter();
const store = useSupportTicketsStore();
const showArchive = ref(false);
const timelineLoading = ref(false);
const transitionStatus = ref('');
const transitionComments = ref('');

const ticket = computed(() => store.currentTicket);
const ingestContact = computed(
  () => parseTicketDescription(ticket.value?.description).contact,
);

onMounted(async () => {
  await Promise.all([store.fetchTicket(route.params.id), store.fetchAgents()]);
  timelineLoading.value = true;
  try {
    await store.fetchTimeline(route.params.id);
  } finally {
    timelineLoading.value = false;
  }
});

function formatDate(value) {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
}

async function onUpdate(payload) {
  await store.updateTicket(route.params.id, payload);
  await store.fetchTimeline(route.params.id);
}

async function onAssign(payload) {
  await store.assignTicket(route.params.id, payload);
  await store.fetchTimeline(route.params.id);
}

async function applyTransition() {
  if (!transitionStatus.value) {
    return;
  }

  await store.transitionTicket(route.params.id, {
    status: transitionStatus.value,
    comments: transitionComments.value || null,
  });
  transitionStatus.value = '';
  transitionComments.value = '';
  await store.fetchTimeline(route.params.id);
}

async function closeTicket() {
  await store.closeTicket(route.params.id);
  await store.fetchTimeline(route.params.id);
}

async function reopenTicket() {
  await store.reopenTicket(route.params.id, { comments: 'Reopened from ticket details' });
  await store.fetchTimeline(route.params.id);
}

async function restoreTicket() {
  await store.restoreTicket(route.params.id);
}

async function confirmArchive() {
  await store.archiveTicket(route.params.id);
  showArchive.value = false;
  await router.push({ name: 'support.tickets.index' });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
