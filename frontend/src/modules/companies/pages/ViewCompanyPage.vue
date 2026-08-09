<template>
  <div>
    <!-- <PageHeader
      :title="company?.company_name || 'Company details'"
      description="Organization overview and structure."
    >
      <template #actions>
        <template v-if="company">
          <RouterLink
            :to="{ name: 'companies.profile', params: { id: company.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Profile
          </RouterLink>
          <RouterLink
            :to="{ name: 'companies.edit', params: { id: company.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="company.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="companiesStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="company">
          <RouterLink
            :to="{ name: 'companies.profile', params: { id: company.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Profile
          </RouterLink>
          <RouterLink
            :to="{ name: 'companies.edit', params: { id: company.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="company.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="companiesStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showDelete = true"
          >
            Delete
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
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="company" class="space-y-6">
      <CompanyCard :company="company" />

      <div class="grid gap-4 sm:grid-cols-3">
        <RouterLink
          :to="{ name: 'companies.departments', params: { id: company.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Departments</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ company.departments_count ?? company.departments?.length ?? 0 }}
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'companies.teams', params: { id: company.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Teams</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ company.teams_count ?? company.teams?.length ?? 0 }}
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'companies.locations', params: { id: company.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Locations</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">
            {{ company.locations_count ?? company.locations?.length ?? 0 }}
          </p>
        </RouterLink>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
          Business information
        </h3>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs text-slate-500">Legal name</dt>
            <dd class="text-sm text-slate-900">{{ company.legal_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Registration</dt>
            <dd class="text-sm text-slate-900">{{ company.registration_number || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Tax number</dt>
            <dd class="text-sm text-slate-900">{{ company.tax_number || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Phone</dt>
            <dd class="text-sm text-slate-900">{{ company.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Language</dt>
            <dd class="text-sm text-slate-900">{{ company.language || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Date / time</dt>
            <dd class="text-sm text-slate-900">
              {{ company.date_format }} · {{ company.time_format }}
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Address</dt>
            <dd class="text-sm text-slate-900">{{ fullAddress }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete company"
      :message="`Soft delete ${company?.company_name || 'this company'}?`"
      confirm-label="Delete"
      :loading="companiesStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CompanyCard from '@/modules/companies/components/CompanyCard.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const router = useRouter();
const companiesStore = useCompaniesStore();
const showDelete = ref(false);

const company = computed(() => companiesStore.currentCompany);
const fullAddress = computed(() => {
  if (!company.value) return '—';
  return (
    [
      company.value.address,
      company.value.city,
      company.value.state,
      company.value.postal_code,
      company.value.country,
    ]
      .filter(Boolean)
      .join(',') || '—'
  );
});

onMounted(() => {
  companiesStore.fetchCompany(route.params.id);
});

async function confirmDelete() {
  await companiesStore.deleteCompany(route.params.id);
  showDelete.value = false;
  await router.push({ name: 'companies.index' });
}

async function restore() {
  await companiesStore.restoreCompany(route.params.id);
  await companiesStore.fetchCompany(route.params.id);
}
</script>
