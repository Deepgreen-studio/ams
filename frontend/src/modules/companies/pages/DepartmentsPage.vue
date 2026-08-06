<template>
  <div>
    <PageHeader title="Departments" :description="`Manage departments for ${companyName}.`">
      <template #actions>
        <RouterLink
          :to="{ name: 'companies.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
      </template>
    </PageHeader>

    <div
      v-if="departmentsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ departmentsStore.error }}
    </div>
    <div
      v-if="departmentsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ departmentsStore.successMessage }}
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
      <h3 class="mb-4 text-sm font-semibold text-slate-900">Add department</h3>
      <form class="grid gap-4 md:grid-cols-4" @submit.prevent="onCreate">
        <input
          v-model="form.name"
          type="text"
          required
          placeholder="Name"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm md:col-span-1"
        />
        <input
          v-model="form.description"
          type="text"
          placeholder="Description"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm md:col-span-2"
        />
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="departmentsStore.saving"
        >
          {{ departmentsStore.saving ? 'Saving...' : 'Add' }}
        </button>
      </form>
    </div>

    <DepartmentTable
      :departments="departmentsStore.departments"
      :loading="departmentsStore.loading"
      @delete="openDelete"
    />
    <div class="mt-4">
      <Pagination
        :meta="departmentsStore.meta"
        :loading="departmentsStore.loading"
        @change="(page) => load(page)"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete department"
      :message="`Delete ${pending?.name || 'this department'}?`"
      :loading="departmentsStore.saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DepartmentTable from '@/modules/companies/components/DepartmentTable.vue';
import { useCompaniesStore, useDepartmentsStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const companiesStore = useCompaniesStore();
const departmentsStore = useDepartmentsStore();
const pending = ref(null);
const form = reactive({ name: '', description: '', status: 'active' });
const companyName = computed(() => companiesStore.currentCompany?.company_name || 'company');

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await load();
});

async function load(page = 1) {
  await departmentsStore.fetchDepartments({ company: route.params.id, page, per_page: 10 });
}

async function onCreate() {
  await departmentsStore.createDepartment({
    company_id: route.params.id,
    name: form.name,
    description: form.description,
    status: form.status,
  });
  form.name = '';
  form.description = '';
  await load();
}

function openDelete(item) {
  pending.value = item;
}

async function confirmDelete() {
  await departmentsStore.deleteDepartment(pending.value.uuid);
  pending.value = null;
  await load();
}
</script>
