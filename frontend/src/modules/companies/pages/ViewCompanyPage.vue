<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="company"
        :to="{ name: 'companies.profile', params: { id: company.uuid } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Profile
      </RouterLink>
      <RouterLink
        v-if="company"
        :to="{ name: 'companies.edit', params: { id: company.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-500" />
        Edit
      </RouterLink>
      <button
        v-if="company && company.deleted_at"
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="companiesStore.saving"
        @click="restore"
      >
        Restore
      </button>
      <button
        v-else-if="company"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
        @click="showDelete = true"
      >
        <TrashIcon class="h-4 w-4 text-white" />
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
      </div>
    </div>

    <DeleteConfirmation
      :open="showDelete"
      title="Delete company"
      :message="deleteMessage"
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
import {
  BuildingOffice2Icon,
  MapPinIcon,
  PencilSquareIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/utils/formatters';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CompanyCard from '@/modules/companies/components/CompanyCard.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const router = useRouter();
const companiesStore = useCompaniesStore();
const showDelete = ref(false);

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
  { label: 'Registration', value: company.value?.registration_number || '-' },
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

const deleteMessage = computed(() => {
  const name = company.value?.company_name || 'this company';
  return `Soft delete ${name}?`;
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
