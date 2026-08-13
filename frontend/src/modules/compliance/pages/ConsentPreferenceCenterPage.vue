<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'compliance.consents.dashboard' }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <Squares2X2Icon class="h-4 w-4" />
        Dashboard
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

    <section class="mb-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:px-8 sm:py-6">
      <div class="mb-5">
        <h2 class="text-base font-semibold text-slate-900">Look up a subject</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Load channel preferences for a company and email, then grant or withdraw each type.
        </p>
      </div>

      <form class="grid gap-4 md:grid-cols-3" @submit.prevent="onLoad">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
          <SelectBox
            v-model="lookup.company_id"
            size="lg"
            placeholder="Select company"
            :options="companySelectOptions"
            :disabled="store.loading"
            :error="Boolean(fieldError('company_id'))"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject email</label>
          <input
            v-model="lookup.subject_email"
            type="email"
            class="input"
            placeholder="name@example.com"
            required
            :disabled="store.loading"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Subject name</label>
          <input
            v-model="lookup.subject_name"
            type="text"
            class="input"
            placeholder="Optional display name"
            :disabled="store.loading"
          />
        </div>
        <div class="flex justify-end md:col-span-3">
          <button
            type="submit"
            class="inline-flex h-11 items-center gap-2 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.loading || !lookup.company_id || !lookup.subject_email"
          >
            <MagnifyingGlassIcon class="h-4 w-4" />
            {{ store.loading ? 'Loading…' : 'Load preferences' }}
          </button>
        </div>
      </form>
    </section>

    <div v-if="store.loading && !hasLoaded" class="space-y-4">
      <div class="grid gap-4 sm:grid-cols-3">
        <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>
      <div class="h-64 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!hasLoaded"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">No subject loaded</p>
      <p class="mt-1 text-xs text-slate-500">
        Choose a company and email to review consent across marketing, analytics, and other channels.
      </p>
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

      <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div
          v-for="card in cards"
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

      <section class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 px-6 py-5 sm:px-8">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Channel preferences</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ subjectMeta }}</p>
          </div>
          <p v-if="dirtyCount" class="text-xs font-medium text-brand-700">
            {{ dirtyCount }} unsaved change{{ dirtyCount === 1 ? '' : 's' }}
          </p>
        </div>

        <div v-if="!rows.length" class="px-6 py-16 text-center sm:px-8">
          <p class="text-sm font-medium text-slate-900">No consent types for this company</p>
          <p class="mt-1 text-xs text-slate-500">Active channels will appear here once they are configured.</p>
        </div>

        <ul v-else class="divide-y divide-zinc-100 px-6 sm:px-8">
          <li
            v-for="row in rows"
            :key="row.consent_type.uuid"
            class="flex items-start justify-between gap-4 py-4"
          >
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-slate-900">{{ row.consent_type.name }}</p>
                <span
                  v-if="row.consent_type.is_required"
                  class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700"
                >
                  Required
                </span>
                <ConsentStatusBadge :status="displayStatus(row)" :label="statusLabel(row)" />
              </div>
              <p class="mt-1 text-xs text-slate-500">
                {{
                  [
                    row.consent_type.description || row.consent_type.channel_label,
                    row.consent_version ? `Version ${row.consent_version}` : null,
                    row.granted !== row.originalGranted ? 'Unsaved' : null,
                  ]
                    .filter(Boolean)
                    .join(' · ')
                }}
              </p>
            </div>
            <button
              type="button"
              role="switch"
              class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition"
              :class="row.granted ? 'bg-brand-600' : 'bg-zinc-200'"
              :aria-checked="row.granted"
              :aria-label="`Toggle ${row.consent_type.name}`"
              :disabled="store.saving || !can('compliance.create')"
              @click="row.granted = !row.granted"
            >
              <span
                class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition"
                :class="row.granted ? 'translate-x-5' : ''"
              />
            </button>
          </li>
        </ul>

        <div
          v-if="rows.length && can('compliance.create')"
          class="flex justify-end border-t border-zinc-100 px-6 py-4 sm:px-8"
        >
          <button
            type="button"
            class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving || !dirtyCount"
            @click="onSave"
          >
            {{ store.saving ? 'Saving…' : 'Save preferences' }}
          </button>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  NoSymbolIcon,
  PlusIcon,
  Squares2X2Icon,
  SquaresPlusIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { companyService } from '@/modules/companies/services/companyService';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import ConsentStatusBadge from '@/modules/compliance/components/ConsentStatusBadge.vue';
import { useConsentStore } from '@/modules/compliance/stores/consents';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const route = useRoute();
const store = useConsentStore();
const toast = useToast();
const { can } = usePermissions();
const companies = ref([]);
const rows = ref([]);
const hasLoaded = ref(false);

const lookup = reactive({
  company_id: '',
  subject_email: '',
  subject_name: '',
});

const companySelectOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name,
  })),
);

const grantedCount = computed(() => rows.value.filter((row) => row.granted).length);
const withdrawnCount = computed(() => rows.value.filter((row) => !row.granted).length);
const dirtyCount = computed(() => rows.value.filter((row) => row.granted !== row.originalGranted).length);
const requiredOffCount = computed(
  () => rows.value.filter((row) => row.consent_type?.is_required && !row.granted).length,
);

const cards = computed(() => [
  {
    label: 'Channels',
    value: rows.value.length,
    hint: 'Active consent types for this company',
    icon: SquaresPlusIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Granted',
    value: grantedCount.value,
    hint: grantedCount.value ? 'Currently opted in' : 'No channels granted',
    icon: CheckCircleIcon,
    iconBg: grantedCount.value ? 'bg-emerald-50' : 'bg-zinc-100',
    iconColor: grantedCount.value ? 'text-emerald-500' : 'text-slate-500',
  },
  {
    label: 'Not granted',
    value: withdrawnCount.value,
    hint: withdrawnCount.value ? 'Withdrawn, pending, or declined' : 'All channels granted',
    icon: NoSymbolIcon,
    iconBg: withdrawnCount.value ? 'bg-rose-50' : 'bg-emerald-50',
    iconColor: withdrawnCount.value ? 'text-rose-500' : 'text-emerald-500',
  },
]);

const subjectMeta = computed(() => {
  const subject = store.preferenceSubject || {};
  return [
    subject.subject_name || lookup.subject_name || 'Subject',
    subject.subject_email || lookup.subject_email,
    selectedCompanyName.value,
  ]
    .filter(Boolean)
    .join(' · ');
});

const selectedCompanyName = computed(
  () => companies.value.find((company) => company.uuid === lookup.company_id)?.company_name || '',
);

const healthMessage = computed(() => {
  if (requiredOffCount.value > 0) {
    return `${requiredOffCount.value} required channel${requiredOffCount.value === 1 ? '' : 's'} ${requiredOffCount.value === 1 ? 'is' : 'are'} not granted.`;
  }
  if (dirtyCount.value > 0) {
    return `${dirtyCount.value} preference${dirtyCount.value === 1 ? '' : 's'} changed and not yet saved.`;
  }
  if (grantedCount.value > 0) {
    return `${grantedCount.value} channel${grantedCount.value === 1 ? '' : 's'} currently granted for this subject.`;
  }
  return 'No channels are granted. Save after updating the preference switches.';
});

const healthTone = computed(() => {
  if (requiredOffCount.value > 0) return 'bg-rose-50 text-rose-800';
  if (dirtyCount.value > 0) return 'bg-amber-50 text-amber-800';
  if (grantedCount.value > 0) return 'bg-emerald-50 text-emerald-800';
  return 'bg-sky-50 text-sky-800';
});

const healthIcon = computed(() => {
  if (requiredOffCount.value > 0) return ExclamationTriangleIcon;
  if (dirtyCount.value > 0) return ExclamationTriangleIcon;
  return CheckCircleIcon;
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
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(async () => {
  store.successMessage = null;
  store.error = null;

  if (route.query.company) lookup.company_id = String(route.query.company);
  if (route.query.subject_email) lookup.subject_email = String(route.query.subject_email);
  if (route.query.subject_name) lookup.subject_name = String(route.query.subject_name);

  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }

  if (lookup.company_id && lookup.subject_email) {
    await onLoad();
  }
});

function fieldError(key) {
  return store.fieldErrors?.[key]?.[0] || '';
}

function displayStatus(row) {
  if (row.granted !== row.originalGranted) {
    return row.granted ? 'granted' : 'withdrawn';
  }
  return row.status || '';
}

function statusLabel(row) {
  if (row.granted !== row.originalGranted) {
    return row.granted ? 'Will grant' : 'Will withdraw';
  }
  return row.status_label || row.status || '';
}

function mapRows(preferences) {
  return (preferences || []).map((row) => ({
    consent_type: row.consent_type,
    granted: Boolean(row.granted),
    originalGranted: Boolean(row.granted),
    status: row.status,
    status_label: row.status_label,
    consent_version: row.consent_version,
  }));
}

async function onLoad() {
  try {
    const data = await store.loadPreferences({
      company_id: lookup.company_id,
      subject_email: lookup.subject_email,
      subject_name: lookup.subject_name || undefined,
    });
    rows.value = mapRows(data?.preferences);
    hasLoaded.value = true;
  } catch {
    hasLoaded.value = false;
    rows.value = [];
  }
}

async function onSave() {
  try {
    await store.savePreferences({
      company_id: lookup.company_id,
      subject_email: lookup.subject_email,
      subject_name: lookup.subject_name || undefined,
      source: 'preference_center',
      preferences: rows.value.map((row) => ({
        consent_type_id: row.consent_type.uuid,
        granted: row.granted,
        consent_version: row.consent_version,
      })),
    });
    toast.success(store.successMessage || 'Consent preferences saved successfully.');
    store.successMessage = null;
    await onLoad();
  } catch {
    // Toast is shown from store.error.
  }
}
</script>
