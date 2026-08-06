<template>
  <div>
    <PageHeader title="SLA Dashboard" description="Response and resolution timers, risk, and breaches">
      <template #actions>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="store.evaluateNow()"
        >
          Evaluate now
        </button>
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

    <div v-if="store.loading && !statistics" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <template v-else>
      <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white lg:col-span-2">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">SLA Timers</h3>
              <p class="text-xs text-slate-500">Live countdown for open tracked tickets</p>
            </div>
            <RouterLink
              :to="{ name: 'support.sla.violations' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View violations
            </RouterLink>
          </div>

          <div v-if="store.timers.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">
            No active SLA timers.
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="timer in store.timers"
              :key="timer.uuid"
              class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                  <RouterLink
                    :to="{ name: 'support.tickets.show', params: { id: timer.uuid } }"
                    class="truncate text-sm font-semibold text-slate-900 hover:text-brand-700"
                  >
                    {{ timer.ticket_number }} · {{ timer.subject }}
                  </RouterLink>
                  <SlaStatusBadge :status="timer.sla_status" :label="timer.sla_status_label" />
                </div>
                <p class="text-xs text-slate-500">
                  {{ timer.company?.company_name || '—' }}
                  · {{ timer.assignee?.full_name || 'Unassigned' }}
                  · {{ timer.policy?.name || 'No policy' }}
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
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
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-900">Escalations</h3>
            <dl class="mt-4 space-y-3 text-sm">
              <div class="flex justify-between">
                <dt class="text-slate-500">Open</dt>
                <dd class="font-semibold text-slate-900">{{ statistics?.escalations?.open ?? 0 }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-slate-500">Pending</dt>
                <dd class="font-semibold text-slate-900">{{ statistics?.escalations?.pending ?? 0 }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-slate-500">Acknowledged</dt>
                <dd class="font-semibold text-slate-900">{{ statistics?.escalations?.acknowledged ?? 0 }}</dd>
              </div>
            </dl>
            <RouterLink
              :to="{ name: 'support.sla.escalations' }"
              class="mt-4 inline-flex text-sm font-medium text-brand-700 hover:underline"
            >
              Open escalation queue
            </RouterLink>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-900">Policies & calendars</h3>
            <p class="mt-2 text-sm text-slate-600">
              Global defaults with optional company overrides, business hours, and holiday calendar.
            </p>
            <div class="mt-4 space-y-2 text-sm">
              <RouterLink
                :to="{ name: 'support.sla.policies' }"
                class="block font-medium text-brand-700 hover:underline"
              >
                Manage policies
              </RouterLink>
              <RouterLink
                :to="{ name: 'support.sla.calendars' }"
                class="block font-medium text-brand-700 hover:underline"
              >
                Business hours & holidays
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SlaCountdown from '@/modules/support/components/SlaCountdown.vue';
import SlaStatusBadge from '@/modules/support/components/SlaStatusBadge.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();
const statistics = computed(() => store.statistics);

const summaryCards = computed(() => [
  { label: 'Tracked', value: statistics.value?.tracked_tickets ?? 0 },
  { label: 'On track', value: statistics.value?.on_track ?? 0 },
  { label: 'At risk', value: statistics.value?.at_risk ?? 0 },
  { label: 'Breached', value: statistics.value?.breached ?? 0 },
]);

onMounted(() => {
  store.fetchDashboard();
});
</script>
