<template>
  <div>
    <!-- <PageHeader title="Company profile" description="Branding, logo, and organizational identity.">
      <template #actions>
        <RouterLink
          v-if="company"
          :to="{ name: 'companies.show', params: { id: company.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to company
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="company"
          :to="{ name: 'companies.show', params: { id: company.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to company
        </RouterLink>
    </Teleport>

    <div
      v-if="companiesStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ companiesStore.successMessage }}
    </div>
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

    <div v-else-if="company" class="grid gap-6 lg:grid-cols-2">
      <div class="space-y-6">
        <CompanyCard :company="company" />
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Logo</h3>
          <CompanyLogoUpload
            :company="company"
            :loading="companiesStore.saving"
            @upload="onLogoUpload"
          />
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
          Branding & locale
        </h3>
        <form class="mt-4 space-y-4" @submit.prevent="onBrandingSubmit">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Primary color</label>
            <input
              v-model="branding.primary_color"
              type="text"
              placeholder="#2563EB"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Secondary color</label>
            <input
              v-model="branding.secondary_color"
              type="text"
              placeholder="#0F172A"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label>
            <input
              v-model="branding.timezone"
              type="text"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Language</label>
            <input
              v-model="branding.language"
              type="text"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Currency</label>
            <input
              v-model="branding.currency"
              type="text"
              maxlength="3"
              class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Date format</label>
              <input
                v-model="branding.date_format"
                type="text"
                class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Time format</label>
              <input
                v-model="branding.time_format"
                type="text"
                class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
              />
            </div>
          </div>
          <div class="flex justify-end">
            <button
              type="submit"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="companiesStore.saving"
            >
              {{ companiesStore.saving ? 'Saving...' : 'Update branding' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import CompanyCard from '@/modules/companies/components/CompanyCard.vue';
import CompanyLogoUpload from '@/modules/companies/components/CompanyLogoUpload.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const companiesStore = useCompaniesStore();
const company = computed(() => companiesStore.currentCompany);

const branding = reactive({
  primary_color: '',
  secondary_color: '',
  timezone: 'UTC',
  language: 'en',
  currency: 'USD',
  date_format: 'Y-m-d',
  time_format: 'H:i',
});

watch(
  company,
  (value) => {
    if (!value) return;
    branding.primary_color = value.branding?.primary_color || value.primary_color || '';
    branding.secondary_color = value.branding?.secondary_color || value.secondary_color || '';
    branding.timezone = value.timezone || 'UTC';
    branding.language = value.language || 'en';
    branding.currency = value.currency || 'USD';
    branding.date_format = value.date_format || 'Y-m-d';
    branding.time_format = value.time_format || 'H:i';
  },
  { immediate: true },
);

onMounted(() => {
  companiesStore.fetchCompany(route.params.id);
});

async function onLogoUpload(file) {
  if (!file) return;
  await companiesStore.uploadLogo(route.params.id, file);
}

async function onBrandingSubmit() {
  await companiesStore.updateBranding(route.params.id, { ...branding });
}
</script>
