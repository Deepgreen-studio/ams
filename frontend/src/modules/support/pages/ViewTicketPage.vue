<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="ticket">
        <RouterLink
          :to="{ name: 'support.tickets.index' }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <ArrowLeftIcon class="h-4 w-4" />
          All tickets
        </RouterLink>
        <RouterLink
          v-if="can('support.update')"
          :to="{ name: 'support.tickets.edit', params: { id: ticket.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4" />
          Edit
        </RouterLink>
        <button
          v-if="ticket.status === 'closed' || ticket.status === 'cancelled'"
          type="button"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="reopenTicket"
        >
          Reopen
        </button>
        <button
          v-else-if="ticket.status !== 'closed'"
          type="button"
          class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="closeTicket"
        >
          Close ticket
        </button>
        <button
          v-if="ticket.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="restoreTicket"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="rounded-[12px] border border-rose-200 bg-white px-5 py-2.5 text-sm font-medium text-rose-700 hover:bg-rose-50"
          @click="showArchive = true"
        >
          Archive
        </button>
      </template>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !ticket" class="space-y-4">
      <div class="h-48 animate-pulse rounded-[12px] bg-zinc-100" />
      <div class="grid gap-4 lg:grid-cols-3">
        <div class="h-64 animate-pulse rounded-[12px] bg-zinc-100 lg:col-span-2" />
        <div class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>
    </div>

    <div
      v-else-if="!ticket"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this ticket</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="loadTicket"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'support.tickets.index' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to tickets
        </RouterLink>
      </div>
    </div>

    <div v-else class="space-y-4">
      <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <TicketStatusBadge :status="ticket.status" :label="ticket.status_label" />
            <PriorityIndicator :priority="ticket.priority" :label="ticket.priority_label" />
            <TicketCategoryBadge :category="ticket.category" :label="ticket.category_label" />
            <span
              v-if="ticket.source"
              class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600"
            >
              {{ ticket.source_label || ticket.source }}
            </span>
          </div>
          <h2 class="text-lg font-semibold text-slate-900">{{ ticket.subject }}</h2>
          <p class="mt-1 text-xs text-slate-500">{{ ticket.ticket_number }}</p>

          <div class="mt-4">
            <TicketDescriptionPanel
              :description="ticket.description || ''"
              :source="ticket.source || ''"
              :has-linked-customer="Boolean(ticket.customer?.display_name)"
            />
          </div>

          <div class="mt-6 rounded-[12px] bg-zinc-50 p-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Change status</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
              <SelectBox
                v-model="transitionStatus"
                wrapper-class="min-w-0 flex-1"
                placeholder="Select next status"
                :options="transitionOptions"
              />
              <button
                type="button"
                class="h-10 shrink-0 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                :disabled="store.saving || !transitionStatus"
                @click="applyTransition"
              >
                Update status
              </button>
            </div>
            <textarea
              v-model="transitionComments"
              rows="2"
              class="mt-3 w-full rounded-[12px] border border-zinc-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
              placeholder="Optional transition comment"
            />
          </div>
        </section>

        <div class="space-y-4">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <div class="mb-3 flex items-center justify-between gap-2">
              <div>
                <h3 class="text-base font-semibold text-slate-900">SLA timers</h3>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ ticket.sla_policy?.name || 'No active policy' }}
                  <span v-if="ticket.escalation_level_label">
                    · Escalation: {{ ticket.escalation_level_label }}
                  </span>
                </p>
              </div>
              <SlaStatusBadge :status="ticket.sla_status" :label="ticket.sla_status_label" />
            </div>
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
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h3 class="text-base font-semibold text-slate-900">Details</h3>
            <dl class="mt-4 divide-y divide-zinc-100 text-sm">
              <div class="py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Ticket number</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ ticket.ticket_number }}</dd>
              </div>
              <div class="py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.company?.company_name || '—' }}</dd>
              </div>
              <div class="py-3 first:pt-0 last:pb-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer</dt>
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
              <div class="py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Application</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.application?.name || '—' }}</dd>
              </div>
              <div class="py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Department / Team</dt>
                <dd class="mt-1 text-slate-900">
                  {{ ticket.department?.name || '—' }} / {{ ticket.team?.name || '—' }}
                </dd>
              </div>
              <div class="py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Assignment</dt>
                <dd class="mt-1 text-slate-900">
                  {{ ticket.assignment_type_label || 'Unassigned' }}
                  <span v-if="ticket.assignee"> · {{ ticket.assignee.full_name }}</span>
                </dd>
              </div>
              <div class="py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Source</dt>
                <dd class="mt-1 text-slate-900">{{ ticket.source_label || ticket.source }}</dd>
              </div>
              <div class="py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created</dt>
                <dd class="mt-1 text-slate-900">{{ formatDate(ticket.created_at) }}</dd>
              </div>
              <div class="py-3 last:pb-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Closed</dt>
                <dd class="mt-1 text-slate-900">{{ formatDate(ticket.closed_at) }}</dd>
              </div>
            </dl>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h3 class="mb-4 text-base font-semibold text-slate-900">Assign ticket</h3>
            <AssignmentPanel
              :company-id="ticket.company?.uuid || ''"
              :agents="store.agents"
              :loading="store.saving"
              submit-label="Apply assignment"
              @submit="onAssign"
            />
          </section>
        </div>
      </div>

      <TicketConversationPanel :ticket-id="ticket.uuid || String(route.params.id)" />

      <TicketStatusTimeline :history="store.timeline" :loading="timelineLoading" />
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
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { ArrowLeftIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AssignmentPanel from '@/modules/support/components/AssignmentPanel.vue';
import PriorityIndicator from '@/modules/support/components/PriorityIndicator.vue';
import SlaCountdown from '@/modules/support/components/SlaCountdown.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import TicketCategoryBadge from '@/modules/support/components/TicketCategoryBadge.vue';
import TicketConversationPanel from '@/modules/support/components/TicketConversationPanel.vue';
import TicketDescriptionPanel from '@/modules/support/components/TicketDescriptionPanel.vue';
import TicketStatusBadge from '@/modules/support/components/TicketStatusBadge.vue';
import TicketStatusTimeline from '@/modules/support/components/TicketStatusTimeline.vue';
import { parseTicketDescription } from '@/modules/support/utils/parseTicketDescription';
import { useSupportTicketsStore } from '@/modules/support/stores/supportTickets';

const route = useRoute();
const router = useRouter();
const store = useSupportTicketsStore();
const toast = useToast();
const { can } = usePermissions();
const showArchive = ref(false);
const timelineLoading = ref(false);
const transitionStatus = ref('');
const transitionComments = ref('');

const ticket = computed(() => store.currentTicket);
const ingestContact = computed(
  () => parseTicketDescription(ticket.value?.description).contact,
);
const transitionOptions = computed(() => ticket.value?.allowed_transitions || []);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message || !ticket.value) return;
    toast.error(message);
    store.error = null;
  },
);

async function loadTicket() {
  store.currentTicket = null;
  store.error = null;
  try {
    await Promise.all([store.fetchTicket(route.params.id), store.fetchAgents()]);
    timelineLoading.value = true;
    try {
      await store.fetchTimeline(route.params.id);
    } finally {
      timelineLoading.value = false;
    }
  } catch {
    /* error is shown via empty state / toast */
  }
}

onMounted(() => {
  loadTicket();
});

function formatDate(value) {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString();
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
