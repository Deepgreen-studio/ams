<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'companies.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to company
      </RouterLink>
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        Add department
      </button>
    </Teleport>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <DepartmentTable
        :departments="departmentsStore.departments"
        :loading="departmentsStore.loading"
        embedded
        @edit="openEdit"
        @delete="openDelete"
      />

      <div class="border-t border-zinc-100 px-6 py-5 sm:px-8">
        <Pagination
          :meta="departmentsStore.meta"
          :loading="departmentsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>

    <DepartmentFormModal
      :open="formOpen"
      :loading="saving"
      :department="editingDepartment"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete department"
      :message="`Delete ${pending?.name || 'this department'}?`"
      :loading="saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DepartmentFormModal from '@/modules/companies/components/DepartmentFormModal.vue';
import DepartmentTable from '@/modules/companies/components/DepartmentTable.vue';
import { useCompaniesStore, useDepartmentsStore } from '@/modules/companies/stores/companies';
import { companyService } from '@/modules/companies/services/companyService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const toast = useToast();
const companiesStore = useCompaniesStore();
const departmentsStore = useDepartmentsStore();
const pending = ref(null);
const editingDepartment = ref(null);
const formOpen = ref(false);
const saving = ref(false);
const perPage = ref(10);

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await load();
});

async function load(page = 1) {
  await departmentsStore.fetchDepartments({
    company: route.params.id,
    page,
    per_page: perPage.value,
  });
}

function onPageChange(page) {
  load(page);
}

function onPerPageChange(value) {
  perPage.value = Number(value) || 10;
  load(1);
}

function openCreate() {
  editingDepartment.value = null;
  formOpen.value = true;
}

function openEdit(item) {
  editingDepartment.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (saving.value) return;
  formOpen.value = false;
  editingDepartment.value = null;
}

async function onSave(payload) {
  saving.value = true;
  try {
    const id = editingDepartment.value?.uuid || editingDepartment.value?.id;
    if (id) {
      const { data } = await companyService.updateDepartment(id, payload);
      toast.success(data.message || 'Department updated successfully.');
    } else {
      const { data } = await companyService.createDepartment({
        company_id: route.params.id,
        ...payload,
      });
      toast.success(data.message || 'Department created successfully.');
    }
    formOpen.value = false;
    editingDepartment.value = null;
    await load(departmentsStore.meta?.current_page || 1);
  } catch (err) {
    toast.error(err?.message || 'Unable to save department.', 'Error');
  } finally {
    saving.value = false;
  }
}

function openDelete(item) {
  pending.value = item;
}

async function confirmDelete() {
  const id = pending.value?.uuid;
  saving.value = true;
  try {
    await companyService.deleteDepartment(id);
    toast.success('Department deleted successfully.');
    if ((editingDepartment.value?.uuid || editingDepartment.value?.id) === id) {
      formOpen.value = false;
      editingDepartment.value = null;
    }
    pending.value = null;
    await load();
  } catch (err) {
    toast.error(err?.message || 'Unable to delete department.', 'Error');
    pending.value = null;
  } finally {
    saving.value = false;
  }
}
</script>
