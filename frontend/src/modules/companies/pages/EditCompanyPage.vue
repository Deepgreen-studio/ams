<template>
  <div>
    <div
      v-if="companiesStore.loading && !companiesStore.currentCompany"
      class="h-64 animate-pulse rounded-[12px] bg-slate-100"
    />
    <div v-else class="rounded-[12px] bg-white p-6 sm:p-8">
      <CompanyForm
        :initial="companiesStore.currentCompany || {}"
        :loading="companiesStore.saving"
        :errors="companiesStore.fieldErrors"
        :error="companiesStore.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'companies.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import CompanyForm from '@/modules/companies/components/CompanyForm.vue';
import { useCompaniesStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const router = useRouter();
const companiesStore = useCompaniesStore();

onMounted(() => {
  companiesStore.fetchCompany(route.params.id);
});

async function onSubmit(payload) {
  await companiesStore.updateCompany(route.params.id, payload);
  await router.push({ name: 'companies.show', params: { id: route.params.id } });
}
</script>
