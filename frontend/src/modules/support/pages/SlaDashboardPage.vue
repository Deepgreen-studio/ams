<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'support.sla.violations' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ExclamationTriangleIcon class="h-4 w-4" />
        Violations
      </RouterLink>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
        @click="evaluateNow"
      >
        <BoltIcon class="h-4 w-4" :class="{ 'animate-pulse': store.saving }" />
        {{ store.saving ? 'Evaluating…' : 'Evaluate now' }}
      </button>
    </Teleport>

    <SupportSubnav />

    <div v-if="store.loading && !statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <template v-else>
      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
            <p v-if="card.hint" class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100 lg:col-span-2">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-6 py-5">
            <div>
              <h2 class="text-base font-semibold text-slate-900">SLA timers</h2>
              <p class="mt-0.5 text-xs text-slate-500">Live countdown for open tracked tickets.</p>
            </div>
            <RouterLink
              :to="{ name: 'support.sla.violations' }"
              class="text-sm font-medium text-brand-700 hover:text-brand-600"
            >
              View violations
            </RouterLink>
          </div>

          <div v-if="store.loading && !store.timers.length" class="space-y-3 px-6 py-5">
            <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>

          <div v-else-if="!store.timers.length" class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">No active SLA timers</p>
            <p class="mt-1 text-xs text-slate-500">Open tracked tickets will appear here.</p>
          </div>

          <ul v-else class="space-y-1 px-3 py-3">
            <li
              v-for="timer in store.timers"
              :key="timer.uuid"
              class="flex flex-col gap-3 rounded-[12px] px-3 py-3 transition hover:bg-zinc-50 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                  <RouterLink
                    :to="{ name: 'support.tickets.show', params: { id: timer.uuid } }"
                    class="truncate text-sm font-semibold text-slate-900 hover:text-brand-700"
                  >
                    {{ timer.ticket_number }}
                    <span class="font-medium text-slate-700">· {{ timer.subject }}</span>
                  </RouterLink>
                  <span
                    v-if="isSms(timer)"
                    class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-sky-100"
                  >
                    SMS
                  </span>
                  <SlaStatusBadge :status="timer.sla_status" :label="timer.sla_status_label" />
                </div>
                <p class="text-xs text-slate-500">
                  {{ timerMeta(timer) }}
                </p>
              </div>
              <div class="flex shrink-0 flex-wrap gap-2">
                <SlaCountdown
                  label="Response"
                  :due-at="timer.first_response_due_at"
                  :completed-at="timer.first_response_at"
                  :remaining-seconds="timer.response_remaining_seconds"
                />
                <SlaCountdown
                  label="Resolution"
                  :due-at="timer.resolution_due_at"
                  :completed-at="timer.resolved_at"
                  :remaining-seconds="timer.resolution_remaining_seconds"
                />
              </div>
            </li>
          </ul>

          <div v-if="store.timerMeta?.total" class="border-t border-zinc-100 px-6 py-4">
            <Pagination
              :meta="store.timerMeta"
              :loading="store.loading"
              @change="onPageChange"
              @per-page="onPerPage"
            />
          </div>
        </section>

        <div class="space-y-4">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Escalations</h2>
            <dl class="mt-4 space-y-3">
              <div
                v-for="row in escalationRows"
                :key="row.label"
                class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
              >
                <dt class="text-sm text-slate-500">{{ row.label }}</dt>
                <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
              </div>
            </dl>
            <RouterLink
              :to="{ name: 'support.sla.escalations' }"
              class="mt-4 inline-flex h-10 items-center rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700"
            >
              Open escalation queue
            </RouterLink>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Policies & calendars</h2>
            <p class="mt-2 text-sm text-slate-600">
              Global defaults with optional company overrides, business hours, and holidays.
            </p>
            <div class="mt-4 flex flex-col gap-2">
              <RouterLink
                :to="{ name: 'support.sla.policies' }"
                class="inline-flex h-10 items-center justify-center rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              >
                Manage policies
              </RouterLink>
              <RouterLink
                :to="{ name: 'support.sla.calendars' }"
                class="inline-flex h-10 items-center justify-center rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              >
                Business hours & holidays
              </RouterLink>
            </div>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  BoltIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import Pagination from '@/modules/users/components/Pagination.vue';
import SlaCountdown from '@/modules/support/components/SlaCountdown.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const toast = useToast();
const perPage = ref(10);
const statistics = computed(() => store.statistics);

const summaryCards = computed(() => {
  const tracked = statistics.value?.tracked_tickets ?? 0;
  const onTrack = statistics.value?.on_track ?? 0;
  const atRisk = statistics.value?.at_risk ?? 0;
  const breached = statistics.value?.breached ?? 0;

  return [
    {
      label: 'Tracked',
      value: tracked,
      hint: 'Tickets with an SLA policy',
      icon: ClockIcon,
      iconBg: tracked ? 'bg-brand-50' : 'bg-zinc-100',
      iconColor: tracked ? 'text-brand-500' : 'text-slate-500',
    },
    {
      label: 'On track',
      value: onTrack,
      hint: 'Within response and resolution',
      icon: CheckCircleIcon,
      iconBg: onTrack ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: onTrack ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'At risk',
      value: atRisk,
      hint: atRisk ? 'Due within the hour' : 'No timers at risk',
      icon: ExclamationTriangleIcon,
      iconBg: atRisk ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: atRisk ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Breached',
      value: breached,
      hint: breached ? 'Needs immediate attention' : 'No active breaches',
      icon: ShieldExclamationIcon,
      iconBg: breached ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: breached ? 'text-rose-500' : 'text-emerald-500',
    },
  ];
});

const escalationRows = computed(() => [
  { label: 'Open', value: statistics.value?.escalations?.open ?? 0 },
  { label: 'Pending', value: statistics.value?.escalations?.pending ?? 0 },
  { label: 'Acknowledged', value: statistics.value?.escalations?.acknowledged ?? 0 },
]);

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
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  loadTimers().catch(() => {});
});

function dashboardParams(page = store.timerMeta?.current_page || 1) {
  return {
    page,
    per_page: perPage.value,
  };
}

async function loadTimers(page = 1) {
  await store.fetchDashboard(dashboardParams(page));
}

function onPageChange(page) {
  loadTimers(page).catch(() => {});
}

function onPerPage(value) {
  perPage.value = value;
  loadTimers(1).catch(() => {});
}

function isSms(timer) {
  return timer.source === 'sms' || String(timer.subject || '').toLowerCase().startsWith('sms support');
}

function timerMeta(timer) {
  return [
    timer.company?.company_name,
    timer.assignee?.full_name || 'Unassigned',
    timer.policy?.name,
  ].filter(Boolean).join(' · ');
}

async function evaluateNow() {
  try {
    await store.evaluateNow(dashboardParams());
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
