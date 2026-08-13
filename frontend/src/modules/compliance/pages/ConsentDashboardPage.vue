<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.consents.preferences' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <AdjustmentsHorizontalIcon class="h-4 w-4" />
        Preference center
      </RouterLink>
      <RouterLink
        v-if="can('compliance.create')"
        :to="{ name: 'compliance.consents.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Record consent
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !hasDashboard" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="store.error && !hasDashboard"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load consent dashboard</p>
      <p class="mt-1 text-xs text-slate-500">Refresh to try loading consent metrics again.</p>
      <button
        type="button"
        class="mt-6 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="reload"
      >
        Retry
      </button>
    </div>

    <template v-else>
      <div
        v-if="healthMessage"
        class="mb-4 flex items-start gap-3 rounded-[12px] px-4 py-3 text-sm"
        :class="healthTone"
      >
        <component :is="healthIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>{{ healthMessage }}</p>
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="card in cards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900">
              {{ card.value }}
            </p>
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
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 lg:col-span-2">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Recent consents</h2>
              <p class="mt-0.5 text-xs text-slate-500">Latest captured or updated preference records</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.consents.index' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              View all
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.recent.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.recent.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No consents yet</p>
            <p class="mt-1 text-xs text-slate-500">Recorded preferences will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="item in store.recent"
              :key="item.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'compliance.consents.show', params: { id: item.uuid } }"
                  class="truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                >
                  {{ item.subject_name || item.subject_email || 'Unknown subject' }}
                </RouterLink>
                <p class="mt-1 text-xs text-slate-500">{{ consentMeta(item) }}</p>
              </div>
              <ConsentStatusBadge :status="item.status" :label="item.status_label" />
            </li>
          </ul>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-base font-semibold text-slate-900">Consent types</h2>
              <p class="mt-0.5 text-xs text-slate-500">Active channels and versions</p>
            </div>
            <RouterLink
              :to="{ name: 'compliance.consents.audit' }"
              class="text-xs font-medium text-brand-700 hover:underline"
            >
              Audit view
            </RouterLink>
          </div>
          <div v-if="store.loading && !store.types.length" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
          </div>
          <div v-else-if="!store.types.length" class="py-10 text-center">
            <p class="text-sm font-medium text-slate-900">No consent types</p>
            <p class="mt-1 text-xs text-slate-500">Configured channels will appear here.</p>
          </div>
          <ul v-else class="divide-y divide-zinc-100">
            <li
              v-for="type in store.types"
              :key="type.uuid"
              class="flex items-start justify-between gap-3 py-3.5 first:pt-0 last:pb-0"
            >
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-900">{{ type.name }}</p>
                <p class="mt-1 text-xs text-slate-500">
                  Version {{ type.current_version || '—' }}
                  <span v-if="type.channel_label"> · {{ type.channel_label }}</span>
                </p>
              </div>
              <span
                v-if="type.is_required"
                class="shrink-0 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700"
              >
                Required
              </span>
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By status</h2>
          <p class="mt-0.5 text-xs text-slate-500">Distribution of all consent records</p>
          <dl class="mt-4 space-y-2.5">
            <div
              v-for="row in statusRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h2 class="text-base font-semibold text-slate-900">By source</h2>
          <p class="mt-0.5 text-xs text-slate-500">Where consent was captured</p>
          <dl v-if="sourceRows.length" class="mt-4 space-y-2.5">
            <div
              v-for="row in sourceRows"
              :key="row.key"
              class="flex items-center justify-between rounded-[12px] bg-zinc-50 px-3.5 py-2.5"
            >
              <dt class="text-sm text-slate-500">{{ row.label }}</dt>
              <dd class="text-sm font-semibold text-slate-900">{{ row.value }}</dd>
            </div>
          </dl>
          <p v-else class="mt-6 text-sm text-slate-500">No capture sources recorded yet.</p>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import {
  AdjustmentsHorizontalIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  NoSymbolIcon,
  PlusIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const store = useConsentStore();
const toast = useToast();
const { can } = usePermissions();

const statistics = computed(() => store.statistics || {});
const hasDashboard = computed(() => Boolean(store.statistics));

const statusLabels = {
  granted: 'Granted',
  pending: 'Pending',
  withdrawn: 'Withdrawn',
  expired: 'Expired',
};

const sourceLabels = {
  web: 'Web',
  mobile: 'Mobile',
  api: 'API',
  preference_center: 'Preference center',
  admin: 'Admin',
  import: 'Import',
  cookie_banner: 'Cookie banner',
};

const cards = computed(() => {
  const stats = statistics.value;
  const granted = stats.granted ?? 0;
  const pending = stats.pending ?? 0;
  const withdrawn = stats.withdrawn ?? 0;
  const expired = stats.expired ?? 0;
  const activeGranted = stats.active_granted ?? 0;

  return [
    {
      label: 'Total records',
      value: stats.total ?? 0,
      hint: 'All captured consent records',
      icon: DocumentTextIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Granted',
      value: granted,
      hint: granted ? 'Subjects currently opted in' : 'No granted consents',
      icon: CheckCircleIcon,
      iconBg: granted ? 'bg-emerald-50' : 'bg-zinc-100',
      iconColor: granted ? 'text-emerald-500' : 'text-slate-500',
    },
    {
      label: 'Active granted',
      value: activeGranted,
      hint: 'Valid and currently in force',
      icon: ShieldCheckIcon,
      iconBg: activeGranted ? 'bg-sky-50' : 'bg-zinc-100',
      iconColor: activeGranted ? 'text-sky-500' : 'text-slate-500',
    },
    {
      label: 'Pending',
      value: pending,
      hint: pending ? 'Awaiting confirmation' : 'Nothing pending',
      icon: ClockIcon,
      iconBg: pending ? 'bg-amber-50' : 'bg-zinc-100',
      iconColor: pending ? 'text-amber-500' : 'text-slate-500',
    },
    {
      label: 'Withdrawn',
      value: withdrawn,
      hint: withdrawn ? 'Opt-outs on record' : 'No withdrawals',
      icon: NoSymbolIcon,
      iconBg: withdrawn ? 'bg-rose-50' : 'bg-zinc-100',
      iconColor: withdrawn ? 'text-rose-500' : 'text-slate-500',
    },
    {
      label: 'Expired',
      value: expired,
      hint: expired ? 'Needs recapture' : 'No expired records',
      icon: ExclamationTriangleIcon,
      iconBg: expired ? 'bg-rose-50' : 'bg-emerald-50',
      iconColor: expired ? 'text-rose-500' : 'text-emerald-500',
    },
  ];
});

const healthMessage = computed(() => {
  const stats = statistics.value;
  const pending = stats.pending ?? 0;
  const expired = stats.expired ?? 0;
  const granted = stats.granted ?? 0;

  if (expired > 0) {
    return `${expired} expired consent record${expired === 1 ? '' : 's'} need recapture.`;
  }
  if (pending > 0) {
    return `${pending} pending consent${pending === 1 ? '' : 's'} awaiting confirmation.`;
  }
  if (granted > 0) {
    return `${granted} granted consent record${granted === 1 ? '' : 's'} currently in force.`;
  }
  return 'Consent records are healthy. No pending or expired preferences.';
});

const healthTone = computed(() => {
  const stats = statistics.value;
  if ((stats.expired ?? 0) > 0) return 'bg-rose-50 text-rose-800';
  if ((stats.pending ?? 0) > 0) return 'bg-amber-50 text-amber-800';
  if ((stats.granted ?? 0) > 0) return 'bg-emerald-50 text-emerald-800';
  return 'bg-sky-50 text-sky-800';
});

const healthIcon = computed(() => {
  const stats = statistics.value;
  if ((stats.expired ?? 0) > 0) return ExclamationTriangleIcon;
  if ((stats.pending ?? 0) > 0) return ClockIcon;
  return ShieldCheckIcon;
});

const statusRows = computed(() => {
  const byStatus = statistics.value.by_status || {};
  return Object.entries(statusLabels).map(([key, label]) => ({
    key,
    label,
    value: Number(byStatus[key] ?? statistics.value[key] ?? 0),
  }));
});

const sourceRows = computed(() => {
  const bySource = statistics.value.by_source || {};
  return Object.entries(bySource)
    .map(([key, value]) => ({
      key,
      label: sourceLabels[key] || key.replaceAll('_', ' '),
      value: Number(value ?? 0),
    }))
    .filter((row) => row.value > 0)
    .sort((a, b) => b.value - a.value);
});

function consentMeta(item) {
  return [
    item.consent_type?.name,
    item.consent_type?.channel_label,
    item.source_label || item.source,
    item.consent_version ? `v${item.consent_version}` : null,
  ]
    .filter(Boolean)
    .join(' · ');
}

async function reload() {
  try {
    await store.fetchDashboard();
  } catch {
    toast.error(store.error || 'Unable to load consent dashboard');
  }
}

onMounted(() => {
  reload();
});
</script>
