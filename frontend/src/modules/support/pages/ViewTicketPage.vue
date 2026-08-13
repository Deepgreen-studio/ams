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

    <div v-if="store.loading && !ticket" class="grid gap-4 lg:grid-cols-3">
      <div class="h-[32rem] animate-pulse rounded-[12px] bg-zinc-100 lg:col-span-2" />
      <div class="h-[32rem] animate-pulse rounded-[12px] bg-zinc-100" />
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

    <div v-else class="grid items-start gap-4 lg:grid-cols-3">
      <div class="space-y-4 lg:col-span-2">
        <section class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
          <div class="flex flex-wrap items-center gap-2">
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
          <h1 class="mt-3 text-lg font-semibold text-slate-900">{{ ticket.subject }}</h1>
          <p class="mt-1 text-xs text-slate-500">{{ ticket.ticket_number }}</p>
        </section>

        <TicketConversationPanel
          :ticket-id="ticket.uuid || String(route.params.id)"
          :opening-body="openingBody"
        />
      </div>

      <aside class="space-y-4">
        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-900">SLA</h2>
            <SlaStatusBadge :status="ticket.sla_status" :label="ticket.sla_status_label" />
          </div>
          <p v-if="ticket.sla_policy?.name" class="mb-3 text-xs text-slate-500">
            {{ ticket.sla_policy.name }}
          </p>
          <div class="grid grid-cols-2 gap-2">
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

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h2 class="text-sm font-semibold text-slate-900">Details</h2>
          <dl class="mt-3 divide-y divide-zinc-100 text-sm">
            <div v-for="item in detailItems" :key="item.label" class="py-2.5 first:pt-0 last:pb-0">
              <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ item.label }}</dt>
              <dd class="mt-1 text-slate-900">
                <span class="font-medium">{{ item.value }}</span>
                <span v-if="item.hint" class="mt-0.5 block text-xs font-normal text-slate-500">
                  {{ item.hint }}
                </span>
              </dd>
            </div>
          </dl>
        </section>

        <section
          v-if="parsed.isIngested && parsed.ingestMeta.length"
          class="rounded-[12px] bg-white ring-1 ring-zinc-100"
        >
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-5 py-3 text-left"
            @click="ingestOpen = !ingestOpen"
          >
            <span class="text-sm font-semibold text-slate-900">Ingest</span>
            <span class="text-xs font-medium text-brand-700">{{ ingestOpen ? 'Hide' : 'Show' }}</span>
          </button>
          <dl v-if="ingestOpen" class="space-y-2 border-t border-zinc-100 px-5 py-3">
            <div v-for="item in parsed.ingestMeta" :key="item.key">
              <dt class="text-[11px] uppercase tracking-wide text-slate-400">{{ item.label }}</dt>
              <dd class="mt-0.5 break-all font-mono text-xs text-slate-700">{{ item.value }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Status</h2>
          <SelectBox
            v-model="transitionStatus"
            placeholder="Next status"
            :options="transitionOptions"
          />
          <textarea
            v-model="transitionComments"
            rows="2"
            class="mt-3 w-full rounded-[12px] border border-zinc-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
            placeholder="Optional comment"
          />
          <button
            type="button"
            class="mt-3 h-10 w-full rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving || !transitionStatus"
            @click="applyTransition"
          >
            Update status
          </button>
        </section>

        <section class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Assign</h2>
          <AssignmentPanel
            :company-id="ticket.company?.uuid || ''"
            :agents="store.agents"
            :loading="store.saving"
            submit-label="Assign"
            @submit="onAssign"
          />
        </section>

        <TicketStatusTimeline :history="store.timeline" :loading="timelineLoading" />
      </aside>
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
const ingestOpen = ref(false);

const ticket = computed(() => store.currentTicket);
const parsed = computed(() => parseTicketDescription(ticket.value?.description));
const ingestContact = computed(() => parsed.value.contact);
const openingBody = computed(() => {
  const body = parsed.value.body || '';
  if (!body) {
    return '';
  }

  const duplicated = (store.messages || []).some((message) => {
    const text = String(message.body || '')
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    return text && (text === body || text.includes(body.slice(0, 80)));
  });

  return duplicated ? '' : body;
});
const transitionOptions = computed(() => ticket.value?.allowed_transitions || []);

const detailItems = computed(() => {
  const current = ticket.value;
  if (!current) {
    return [];
  }

  const items = [];

  if (current.company?.company_name) {
    items.push({ label: 'Company', value: current.company.company_name });
  }

  if (current.customer?.display_name) {
    items.push({
      label: 'Customer',
      value: current.customer.display_name,
      hint: current.customer.email || '',
    });
  } else if (ingestContact.value.name || ingestContact.value.email || ingestContact.value.from) {
    items.push({
      label: 'Customer',
      value: ingestContact.value.name || ingestContact.value.from,
      hint:
        ingestContact.value.email ||
        (ingestContact.value.from &&
        ingestContact.value.from !== ingestContact.value.name
          ? ingestContact.value.from
          : ''),
    });
  }

  if (current.application?.name) {
    items.push({ label: 'Application', value: current.application.name });
  }

  const org = [current.department?.name, current.team?.name].filter(Boolean).join(' / ');
  if (org) {
    items.push({ label: 'Team', value: org });
  }

  items.push({
    label: 'Assignee',
    value: current.assignee?.full_name || 'Unassigned',
  });

  items.push({ label: 'Created', value: formatDate(current.created_at) });

  if (current.closed_at) {
    items.push({ label: 'Closed', value: formatDate(current.closed_at) });
  }

  return items;
});

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
