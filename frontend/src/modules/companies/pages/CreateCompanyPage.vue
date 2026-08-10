<template>
  <div>
    <div class="rounded-[12px] bg-white p-6 sm:p-8">
      <CompanyForm
        :loading="companiesStore.saving"
        :errors="companiesStore.fieldErrors"
        :error="companiesStore.error || ''"
        submit-label="Create company"
        @submit="onSubmit"
        @cancel="router.push({ name: 'companies.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import CompanyForm from '@/modules/companies/components/CompanyForm.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const router = useRouter();
const companiesStore = useCompaniesStore();

async function onSubmit(payload) {
  const company = await companiesStore.createCompany(payload);
  await router.push({ name: 'companies.show', params: { id: company.uuid } });
}
</script>
