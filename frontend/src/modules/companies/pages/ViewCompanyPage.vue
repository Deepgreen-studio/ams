<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="company"
        :to="{ name: 'companies.profile', params: { id: company.uuid } }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Profile
      </RouterLink>
      <RouterLink
        v-if="company && can('companies.update')"
        :to="{ name: 'companies.edit', params: { id: company.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-500" />
        Edit
      </RouterLink>
      <button
        v-if="company && company.deleted_at && can('companies.restore')"
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="companiesStore.saving"
        @click="restore"
      >
        Restore
      </button>
      <button
        v-else-if="company && can('companies.delete')"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
        @click="showDelete = true"
      >
        <TrashIcon class="h-4 w-4 text-white" />
        Soft Delete
      </button>
    </Teleport>

    <div
      v-if="companiesStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ companiesStore.error }}
    </div>

    <div
      v-if="companiesStore.loading && !company"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="company" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <CompanyCard :company="company" />

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Business information</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in businessItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900">{{ item.value }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Organization</h3>
          <div class="mt-4 space-y-2.5">
            <RouterLink
              v-for="item in orgLinks"
              :key="item.to"
              :to="{ name: item.to, params: { id: company.uuid } }"
              class="flex items-center justify-between gap-3 rounded-[12px] bg-zinc-50 px-4 py-3.5 transition hover:bg-zinc-100"
            >
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] bg-white text-slate-500 ring-1 ring-zinc-100"
                >
                  <component :is="item.icon" class="h-4 w-4" />
                </span>
                <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-lg font-semibold text-slate-900">{{ item.count }}</span>
                <span class="text-sm font-medium text-brand-600">Manage</span>
              </div>
            </RouterLink>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Details</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Status</dt>
              <dd><StatusBadge :status="company.status || 'active'" /></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Language</dt>
              <dd class="text-sm font-medium text-slate-900">{{ company.language || '-' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(company.created_at) || '-' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(company.updated_at) || '-' }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Audit log</h3>
          <p v-if="logsLoading" class="mt-4 text-sm text-slate-500">Loading audit log…</p>
          <p v-else-if="!activities.length" class="mt-4 text-sm text-slate-500">No audit entries yet.</p>
          <ul
            v-else
            class="mt-4 max-h-80 divide-y divide-slate-100 overflow-y-auto overscroll-contain rounded-[12px] bg-slate-50/60"
          >
            <li v-for="entry in visibleActivities" :key="entry.id" class="px-3.5 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  v-if="entry.action === 'status_changed'"
                  class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-amber-200"
                >
                  Status change
                </span>
                <p class="text-sm font-medium text-slate-900">{{ entry.description }}</p>
              </div>
              <p class="mt-1 text-xs text-slate-500">
                {{ entry.user?.full_name || 'System' }}
                <span class="px-1">·</span>
                {{ formatDateTime(entry.created_at) || '-' }}
              </p>
            </li>
          </ul>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Important log</h3>
          <p v-if="logsLoading" class="mt-4 text-sm text-slate-500">Loading important log…</p>
          <p v-else-if="!importantEvents.length" class="mt-4 text-sm text-slate-500">No important events yet.</p>
          <ul
            v-else
            class="mt-4 max-h-64 divide-y divide-slate-100 overflow-y-auto overscroll-contain rounded-[12px] bg-slate-50/60"
          >
            <li v-for="entry in importantEvents" :key="entry.uuid || entry.id" class="px-3.5 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1"
                  :class="importantTone(entry.level)"
                >
                  {{ importantLabel(entry.level) }}
                </span>
                <p class="text-sm font-medium text-slate-900">{{ importantText(entry) }}</p>
              </div>
              <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(entry.created_at) || '-' }}</p>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Soft delete company"
      :message="deleteMessage"
      confirm-label="Soft Delete"
      :loading="companiesStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  BuildingOffice2Icon,
  MapPinIcon,
  PencilSquareIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { formatDate, formatDateTime } from '@/utils/formatters';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CompanyCard from '@/modules/companies/components/CompanyCard.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const router = useRouter();
const companiesStore = useCompaniesStore();
const { can } = usePermissions();
const showDelete = ref(false);
const logsLoading = ref(false);
const activities = ref([]);
const importantEvents = ref([]);

const STATUS_LABELS = {
  active: 'Active',
  inactive: 'Inactive',
  suspended: 'Suspended',
  pending: 'Pending',
};

const company = computed(() => companiesStore.currentCompany);

const fullAddress = computed(() => {
  if (!company.value) {
    return '-';
  }

  const parts = [
    company.value.address,
    company.value.city,
    company.value.state,
    company.value.postal_code,
    company.value.country,
  ].filter(Boolean);

  return parts.length ? parts.join(', ') : '-';
});

const businessItems = computed(() => [
  { label: 'Legal name', value: company.value?.legal_name || '-' },
  { label: 'Company Code', value: company.value?.registration_number || '-' },
  { label: 'Tax number', value: company.value?.tax_number || '-' },
  { label: 'Date format', value: company.value?.date_format || '-' },
  { label: 'Time format', value: company.value?.time_format || '-' },
  { label: 'Address', value: fullAddress.value },
]);

const orgLinks = computed(() => [
  {
    label: 'Departments',
    to: 'companies.departments',
    icon: BuildingOffice2Icon,
    count: company.value?.departments_count ?? company.value?.departments?.length ?? 0,
  },
  {
    label: 'Teams',
    to: 'companies.teams',
    icon: UserGroupIcon,
    count: company.value?.teams_count ?? company.value?.teams?.length ?? 0,
  },
  {
    label: 'Locations',
    to: 'companies.locations',
    icon: MapPinIcon,
    count: company.value?.locations_count ?? company.value?.locations?.length ?? 0,
  },
]);

const visibleActivities = computed(() => {
  const rows = activities.value;
  const statusTimes = new Set(
    rows.filter((entry) => statusDiff(entry)).map((entry) => entry.created_at),
  );

  return rows.flatMap((entry) => {
    const diff = statusDiff(entry);
    if (diff) {
      return [{
        ...entry,
        action: 'status_changed',
        description: statusUpdateText(diff.from, diff.to),
      }];
    }

    const genericUpdate = entry.description === 'Company updated'
      || entry.properties?.event === 'company_updated';
    if (genericUpdate && statusTimes.has(entry.created_at)) {
      return [];
    }

    return [entry];
  });
});

const deleteMessage = computed(() => {
  const name = company.value?.company_name || 'this company';
  return `Soft delete ${name}?`;
});

onMounted(() => {
  companiesStore.fetchCompany(route.params.id);
  loadLogs();
});

async function loadLogs() {
  logsLoading.value = true;
  try {
    const { data } = await companyService.activity(route.params.id);
    activities.value = data.data?.activities ?? [];
    importantEvents.value = data.data?.important_events ?? [];
  } catch {
    activities.value = [];
    importantEvents.value = [];
  } finally {
    logsLoading.value = false;
  }
}

function statusName(value) {
  if (!value) {
    return '';
  }

  return STATUS_LABELS[value] || `${String(value).charAt(0).toUpperCase()}${String(value).slice(1)}`;
}

function statusDiff(entry) {
  const props = entry.properties || {};
  const from = props.old_status || props.old?.status || null;
  const to = props.new_status || props.attributes?.status || null;
  const changedKeys = [...new Set([
    ...Object.keys(props.old || {}),
    ...Object.keys(props.attributes || {}),
  ])];
  const statusOnly = changedKeys.length === 0 || (changedKeys.length === 1 && changedKeys[0] === 'status');
  const markedStatus = entry.action === 'status_changed' || props.event === 'status_changed';

  if (from && to && from !== to && (statusOnly || markedStatus)) {
    return { from, to };
  }

  if (markedStatus && (from || to)) {
    return { from, to };
  }

  return null;
}

function statusUpdateText(from, to) {
  const previous = statusName(from);
  const next = statusName(to);

  if (previous && next) {
    return `Status updated from ${previous} to ${next}`;
  }

  return `Status updated to ${next || previous}`;
}

function importantLabel(level) {
  return ['warning', 'warn', 'error', 'critical', 'alert', 'emergency'].includes(level)
    ? 'Important'
    : 'Notice';
}

function importantTone(level) {
  return ['warning', 'warn', 'error', 'critical', 'alert', 'emergency'].includes(level)
    ? 'bg-rose-50 text-rose-700 ring-rose-200'
    : 'bg-sky-50 text-sky-700 ring-sky-200';
}

function importantText(entry) {
  const payload = entry.payload || {};
  const from = statusLabel(payload.old_status);
  const to = statusLabel(payload.new_status);

  if (entry.event === 'company.status_changed' && from && to) {
    return `Status updated from ${from} to ${to}`;
  }

  const labels = {
    'company.created': 'Company created',
    'company.deleted': 'Company deleted',
    'company.restored': 'Company restored',
    'company.status_changed': 'Company status changed',
  };

  return labels[entry.event] || entry.event;
}

function statusLabel(status) {
  if (!status) {
    return '';
  }

  return String(status).charAt(0).toUpperCase() + String(status).slice(1);
}

async function confirmDelete() {
  await companiesStore.deleteCompany(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'companies.index' });
}

async function restore() {
  await companiesStore.restoreCompany(route.params.id);
  await companiesStore.fetchCompany(route.params.id);
  await loadLogs();
}
</script>
