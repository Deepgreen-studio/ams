<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'companies.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to company
      </RouterLink>
    </Teleport>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
          <input
            v-model="form.name"
            type="text"
            placeholder="Department name"
            class="h-10 min-w-0 flex-1 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0 lg:max-w-xs"
            @keyup.enter="onSave"
          />
          <input
            v-model="form.description"
            type="text"
            placeholder="Description (optional)"
            class="h-10 min-w-0 flex-1 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            @keyup.enter="onSave"
          />
          <SelectBox
            v-model="form.status"
            wrapper-class="min-w-[9.5rem]"
            :options="statusOptions"
          />
          <button
            type="button"
            class="h-10 shrink-0 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="saving || !form.name.trim()"
            @click="onSave"
          >
            {{ submitLabel }}
          </button>
          <button
            v-if="editingId"
            type="button"
            class="h-10 shrink-0 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="cancelEdit"
          >
            Cancel
          </button>
        </div>
      </div>

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
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import DepartmentTable from '@/modules/companies/components/DepartmentTable.vue';
import { useCompaniesStore, useDepartmentsStore } from '@/modules/companies/stores/companies';
import { companyService } from '@/modules/companies/services/companyService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const toast = useToast();
const companiesStore = useCompaniesStore();
const departmentsStore = useDepartmentsStore();
const pending = ref(null);
const editingId = ref(null);
const saving = ref(false);
const perPage = ref(10);

const form = reactive({
  name: '',
  description: '',
  status: 'active',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const submitLabel = computed(() => {
  if (saving.value) return 'Saving...';
  return editingId.value ? 'Update department' : 'Add department';
});

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

function resetForm() {
  form.name = '';
  form.description = '';
  form.status = 'active';
  editingId.value = null;
}

function openEdit(item) {
  editingId.value = String(item.uuid || item.id || '');
  form.name = item.name || '';
  form.description = item.description || '';
  form.status = item.status || 'active';
}

function cancelEdit() {
  resetForm();
}

async function onSave() {
  const name = form.name.trim();
  if (!name) {
    toast.error('Department name is required.', 'Validation Failed');
    return;
  }

  const payload = {
    name,
    description: form.description.trim() ? form.description.trim() : null,
    status: form.status || 'active',
  };

  saving.value = true;
  try {
    if (editingId.value) {
      const { data } = await companyService.updateDepartment(editingId.value, payload);
      toast.success(data.message || 'Department updated successfully.');
    } else {
      const { data } = await companyService.createDepartment({
        company_id: route.params.id,
        ...payload,
      });
      toast.success(data.message || 'Department created successfully.');
    }
    resetForm();
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
    if (editingId.value === id) resetForm();
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
