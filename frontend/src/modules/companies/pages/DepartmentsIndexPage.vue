<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        v-if="can('companies.update')"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        <PlusIcon class="h-4 w-4" />
        Create Department
      </button>
    </Teleport>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <DepartmentTable
        :departments="departmentsStore.departments"
        :loading="departmentsStore.loading"
        :columns="columns"
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

    <div
      v-if="formOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="closeForm"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true">
        <h3 class="text-lg font-semibold text-slate-900">
          {{ editing ? 'Edit department' : 'Create department' }}
        </h3>
        <form class="mt-5 space-y-4" @submit.prevent="onSave">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Department Name</label>
            <input
              v-model="form.department_name"
              type="text"
              maxlength="150"
              required
              class="h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none focus:border-brand-500"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
            <SearchableSelect
              v-model="form.company_id"
              :options="companyOptions"
              placeholder="Select a company"
              search-placeholder="Search company…"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Note</label>
            <textarea
              v-model="form.note"
              rows="3"
              maxlength="2000"
              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none focus:border-brand-500"
            />
          </div>
          <p v-if="formError" class="text-sm text-rose-600">{{ formError }}</p>
          <div class="flex justify-end gap-2 pt-1">
            <button
              type="button"
              class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              :disabled="departmentsStore.saving"
              @click="closeForm"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="departmentsStore.saving"
            >
              {{ departmentsStore.saving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete department"
      :message="`Delete ${pending?.department_name || pending?.name || 'this department'}?`"
      :loading="departmentsStore.saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, reactive, ref } from 'vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { useToast } from '@/composables/useToast';
import { usePermissions } from '@/composables/usePermissions';
import { companyService } from '@/modules/companies/services/companyService';
import DepartmentTable from '@/modules/companies/components/DepartmentTable.vue';
import { useDepartmentsStore } from '@/modules/companies/stores/companies';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const columns = [
  { key: 'name', label: 'Department name' },
  { key: 'company', label: 'Company' },
  { key: 'note', label: 'Note' },
];

const toast = useToast();
const { can } = usePermissions();
const departmentsStore = useDepartmentsStore();
const companies = ref([]);
const formOpen = ref(false);
const editing = ref(null);
const pending = ref(null);
const formError = ref('');
const page = ref(1);
const perPage = ref(10);
const form = reactive({
  department_name: '',
  company_id: '',
  note: '',
});

const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name || company.legal_name || company.uuid,
  })),
);

onMounted(async () => {
  await Promise.all([load(), loadCompanies()]);
});

async function loadCompanies() {
  try {
    const { data } = await companyService.list({
      per_page: 100,
      sort_by: 'company_name',
      sort_dir: 'asc',
      page: 1,
    });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
}

async function load() {
  await departmentsStore.fetchDepartments({
    page: page.value,
    per_page: perPage.value,
  });
}

function openCreate() {
  editing.value = null;
  form.department_name = '';
  form.company_id = '';
  form.note = '';
  formError.value = '';
  formOpen.value = true;
}

function openEdit(department) {
  editing.value = department;
  form.department_name = department.department_name || department.name || '';
  form.company_id = department.company?.uuid || '';
  form.note = department.note || '';
  formError.value = '';
  formOpen.value = true;
}

function closeForm() {
  if (departmentsStore.saving) return;
  formOpen.value = false;
}

function openDelete(department) {
  pending.value = department;
}

async function onSave() {
  formError.value = '';
  if (!form.department_name.trim() || !form.company_id) {
    formError.value = 'Department name and company are required.';
    return;
  }

  const payload = {
    department_name: form.department_name.trim(),
    company_id: form.company_id,
    note: form.note.trim() || null,
  };

  try {
    if (editing.value) {
      await departmentsStore.updateDepartment(editing.value.uuid, payload);
      toast.success('Department updated successfully.');
    } else {
      await departmentsStore.createDepartment(payload);
      toast.success('Department created successfully.');
    }
    formOpen.value = false;
    await load();
  } catch (err) {
    formError.value = err?.message || 'Unable to save department.';
  }
}

async function confirmDelete() {
  if (!pending.value) return;
  try {
    await departmentsStore.deleteDepartment(pending.value.uuid);
    toast.success('Department deleted successfully.');
    pending.value = null;
    await load();
  } catch (err) {
    toast.error(err?.message || 'Unable to delete department.');
  }
}

function onPageChange(nextPage) {
  page.value = nextPage;
  load();
}

function onPerPageChange(value) {
  perPage.value = value;
  page.value = 1;
  load();
}
</script>
