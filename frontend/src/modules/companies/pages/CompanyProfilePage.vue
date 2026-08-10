<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="company"
        :to="{ name: 'companies.show', params: { id: company.uuid } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to company
      </RouterLink>
    </Teleport>

    <div
      v-if="companiesStore.loading && !company"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="company" class="grid gap-6 xl:grid-cols-12">
      <aside class="space-y-6 xl:col-span-4">
        <div class="rounded-[12px] bg-white p-6">
          <CompanyLogoUpload
            :company="company"
            :loading="companiesStore.saving"
            @upload="onLogoUpload"
          />

          <div class="mt-6 border-t border-slate-100 pt-5">
            <div class="flex flex-wrap items-center justify-center gap-2">
              <h2 class="truncate text-lg font-semibold tracking-tight text-slate-900">
                {{ company.company_name }}
              </h2>
              <StatusBadge :status="company.status || 'active'" />
            </div>
            <p class="mt-1 truncate text-center text-sm text-slate-500">
              {{ company.email || company.legal_name || '-' }}
            </p>

            <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
              <div
                v-for="item in summaryItems"
                :key="item.label"
                class="grid grid-cols-[6.5rem_1fr] gap-3 px-3.5 py-3"
              >
                <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
                <dd class="truncate text-sm font-medium text-slate-900">{{ item.value }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </aside>

      <section class="xl:col-span-8">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <form class="space-y-8" @submit.prevent="onBrandingSubmit">
            <div class="grid gap-x-10 gap-y-5 md:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Primary color</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="branding.primary_color"
                    type="color"
                    class="h-12 w-12 shrink-0 cursor-pointer rounded-[12px] border border-slate-200 bg-white p-1"
                  />
                  <input
                    v-model="branding.primary_color"
                    type="text"
                    placeholder="#2563EB"
                    class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
                  />
                </div>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Secondary color</label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="branding.secondary_color"
                    type="color"
                    class="h-12 w-12 shrink-0 cursor-pointer rounded-[12px] border border-slate-200 bg-white p-1"
                  />
                  <input
                    v-model="branding.secondary_color"
                    type="text"
                    placeholder="#0F172A"
                    class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
                  />
                </div>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Timezone</label>
                <SearchableSelect
                  v-model="branding.timezone"
                  :options="timezoneOptions"
                  placeholder="Select timezone"
                  search-placeholder="Search timezone…"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Language</label>
                <SearchableSelect
                  v-model="branding.language"
                  :options="languageOptions"
                  placeholder="Select language"
                  search-placeholder="Search language…"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Currency</label>
                <SelectBox v-model="branding.currency" size="lg" :options="currencyOptions" />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Date format</label>
                <SelectBox v-model="branding.date_format" size="lg" :options="dateFormatOptions" />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Time format</label>
                <SelectBox v-model="branding.time_format" size="lg" :options="timeFormatOptions" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
                :disabled="companiesStore.saving"
                @click="resetBranding"
              >
                Reset
              </button>
              <button
                type="submit"
                class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
                :disabled="companiesStore.saving"
              >
                {{ companiesStore.saving ? 'Saving...' : 'Update branding' }}
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import CompanyLogoUpload from '@/modules/companies/components/CompanyLogoUpload.vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';
import { useToast } from '@/composables/useToast';
import { getTimezoneOptions, LANGUAGE_OPTIONS } from '@/utils/localeOptions';

const route = useRoute();
const companiesStore = useCompaniesStore();
const toast = useToast();
const company = computed(() => companiesStore.currentCompany);
const timezoneOptionsBase = getTimezoneOptions();

const currencyOptionsBase = [
  { value: 'USD', label: 'USD — US Dollar' },
  { value: 'GBP', label: 'GBP — British Pound' },
  { value: 'EUR', label: 'EUR — Euro' },
  { value: 'CAD', label: 'CAD — Canadian Dollar' },
  { value: 'AUD', label: 'AUD — Australian Dollar' },
  { value: 'INR', label: 'INR — Indian Rupee' },
  { value: 'BDT', label: 'BDT — Bangladeshi Taka' },
  { value: 'JPY', label: 'JPY — Japanese Yen' },
  { value: 'CNY', label: 'CNY — Chinese Yuan' },
  { value: 'SGD', label: 'SGD — Singapore Dollar' },
  { value: 'AED', label: 'AED — UAE Dirham' },
];

const dateFormatOptionsBase = [
  { value: 'Y-m-d', label: 'Y-m-d (2026-08-10)' },
  { value: 'd/m/Y', label: 'd/m/Y (10/08/2026)' },
  { value: 'm/d/Y', label: 'm/d/Y (08/10/2026)' },
  { value: 'd-m-Y', label: 'd-m-Y (10-08-2026)' },
  { value: 'd M Y', label: 'd M Y (10 Aug 2026)' },
];

const timeFormatOptionsBase = [
  { value: 'H:i', label: '24-hour (14:30)' },
  { value: 'h:i A', label: '12-hour (02:30 PM)' },
];

const branding = reactive({
  primary_color: '#2563EB',
  secondary_color: '#0F172A',
  timezone: 'UTC',
  language: 'en',
  currency: 'USD',
  date_format: 'Y-m-d',
  time_format: 'H:i',
});

function withCurrentOption(options, current) {
  if (current && !options.some((option) => option.value === current)) {
    return [{ value: current, label: current }, ...options];
  }
  return options;
}

const timezoneOptions = computed(() => withCurrentOption(timezoneOptionsBase, branding.timezone));
const languageOptions = computed(() => withCurrentOption(LANGUAGE_OPTIONS, branding.language));
const currencyOptions = computed(() => withCurrentOption(currencyOptionsBase, branding.currency));
const dateFormatOptions = computed(() => withCurrentOption(dateFormatOptionsBase, branding.date_format));
const timeFormatOptions = computed(() => withCurrentOption(timeFormatOptionsBase, branding.time_format));

const summaryItems = computed(() => [
  { label: 'Country', value: company.value?.country || '-' },
  { label: 'Timezone', value: company.value?.timezone || '-' },
  { label: 'Currency', value: company.value?.currency || '-' },
  { label: 'Language', value: company.value?.language || '-' },
]);

function applyBranding(value) {
  if (!value) return;
  branding.primary_color = value.branding?.primary_color || value.primary_color || '#2563EB';
  branding.secondary_color = value.branding?.secondary_color || value.secondary_color || '#0F172A';
  branding.timezone = value.timezone || 'UTC';
  branding.language = value.language || 'en';
  branding.currency = value.currency || 'USD';
  branding.date_format = value.date_format || 'Y-m-d';
  branding.time_format = value.time_format || 'H:i';
}

watch(company, (value) => applyBranding(value), { immediate: true });

watch(
  () => companiesStore.successMessage,
  (message) => {
    if (message) toast.success(message);
  },
);

watch(
  () => companiesStore.error,
  (message) => {
    if (message) toast.error(message, 'Error');
  },
);

onMounted(() => {
  companiesStore.fetchCompany(route.params.id);
});

function resetBranding() {
  applyBranding(company.value);
}

async function onLogoUpload(file) {
  if (!file) return;
  await companiesStore.uploadLogo(route.params.id, file);
}

async function onBrandingSubmit() {
  await companiesStore.updateBranding(route.params.id, { ...branding });
}
</script>
