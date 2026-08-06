<template>
  <div>
    <PageHeader
      title="Preference Center"
      description="Load and update a subject’s consent preferences across all channels."
    />
    <ComplianceSubnav />

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

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
      <form class="grid gap-4 md:grid-cols-3" @submit.prevent="onLoad">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
          <select v-model="lookup.company_id" class="input" required>
            <option value="" disabled>Select company</option>
            <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
              {{ company.company_name }}
            </option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Subject email</label>
          <input v-model="lookup.subject_email" type="email" class="input" required />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Subject name</label>
          <input v-model="lookup.subject_name" type="text" class="input" />
        </div>
        <div class="md:col-span-3 flex justify-end">
          <button
            type="submit"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.loading"
          >
            Load preferences
          </button>
        </div>
      </form>
    </div>

    <div v-if="rows.length" class="rounded-xl border border-slate-200 bg-white p-5">
      <div class="mb-4">
        <h2 class="text-sm font-semibold text-slate-900">Channel preferences</h2>
        <p class="text-xs text-slate-500">
          {{ store.preferenceSubject?.subject_name || lookup.subject_name || 'Subject' }}
          · {{ store.preferenceSubject?.subject_email || lookup.subject_email }}
        </p>
      </div>

      <div class="space-y-3">
        <label
          v-for="row in rows"
          :key="row.consent_type.uuid"
          class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"
        >
          <div>
            <p class="font-medium text-slate-900">{{ row.consent_type.name }}</p>
            <p class="text-xs text-slate-500">
              {{ row.consent_type.description || row.consent_type.channel_label }}
              · Version {{ row.consent_version }}
            </p>
          </div>
          <input v-model="row.granted" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300" />
        </label>
      </div>

      <div class="mt-4 flex justify-end">
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="onSave"
        >
          {{ store.saving ? 'Saving...' : 'Save preferences' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useConsentStore } from '@/modules/compliance/stores/consents';

const store = useConsentStore();
const companies = ref([]);
const rows = ref([]);

const lookup = reactive({
  company_id: '',
  subject_email: '',
  subject_name: '',
});

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

async function onLoad() {
  const data = await store.loadPreferences({
    company_id: lookup.company_id,
    subject_email: lookup.subject_email,
    subject_name: lookup.subject_name || undefined,
  });
  rows.value = (data?.preferences || []).map((row) => ({
    consent_type: row.consent_type,
    granted: Boolean(row.granted),
    consent_version: row.consent_version,
  }));
}

async function onSave() {
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
  await onLoad();
}
</script>
