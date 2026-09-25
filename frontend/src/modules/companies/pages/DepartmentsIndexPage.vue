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
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8">
        <div class="relative max-w-sm">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            v-model="search"
            type="search"
            placeholder="Search department, company, or team"
            class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            @input="onSearchInput"
            @search="onSearchInput"
          />
        </div>
      </div>

      <DepartmentTable
        :departments="departmentsStore.departments"
        :loading="departmentsStore.loading"
        :columns="columns"
        :empty-title="emptyState.title"
        :empty-description="emptyState.description"
        :sort-by="sortBy"
        :sort-dir="sortDir"
        embedded
        @sort="onSort"
        @view="openView"
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
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
            <SelectBox
              v-model="form.status"
              size="lg"
              wrapper-class="w-full"
              :options="statusOptions"
              :disabled="departmentsStore.saving"
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
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useToast } from '@/composables/useToast';
import { usePermissions } from '@/composables/usePermissions';
import { companyService } from '@/modules/companies/services/companyService';
import DepartmentTable from '@/modules/companies/components/DepartmentTable.vue';
import { useDepartmentsStore } from '@/modules/companies/stores/companies';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const columns = [
  { key: 'name', label: 'Department name', sortable: true },
  { key: 'company', label: 'Company', sortable: true },
  { key: 'team', label: 'Team name' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Created at', sortable: true },
];

const toast = useToast();
const router = useRouter();
const { can } = usePermissions();
const departmentsStore = useDepartmentsStore();
const companies = ref([]);
const formOpen = ref(false);
const editing = ref(null);
const pending = ref(null);
const formError = ref('');
const page = ref(1);
const perPage = ref(10);
const search = ref('');
const sortBy = ref('name');
const sortDir = ref('asc');
let searchTimer = null;

const emptyState = computed(() => {
  if (search.value.trim()) {
    return {
      title: 'No departments found',
      description: 'No departments match this search.',
    };
  }

  return {
    title: 'No departments',
    description: 'Add a department to organize your company.',
  };
});
const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const form = reactive({
  department_name: '',
  company_id: '',
  note: '',
  status: 'active',
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
    search: search.value.trim() || undefined,
    sort_by: sortBy.value,
    sort_dir: sortDir.value,
  });
}

function onSort(column) {
  sortDir.value = sortBy.value === column && sortDir.value === 'asc' ? 'desc' : 'asc';
  sortBy.value = column;
  page.value = 1;
  load();
}

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = search.value.trim() ? 300 : 0;
  searchTimer = window.setTimeout(() => {
    page.value = 1;
    load();
  }, delay);
}

function openView(department) {
  router.push({ name: 'departments.show', params: { id: department.uuid } });
}

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
});

function openCreate() {
  editing.value = null;
  form.department_name = '';
  form.company_id = '';
  form.note = '';
  form.status = 'active';
  formError.value = '';
  formOpen.value = true;
}

function openEdit(department) {
  editing.value = department;
  form.department_name = department.department_name || department.name || '';
  form.company_id = department.company?.uuid || '';
  form.note = department.note || '';
  form.status = department.status || 'active';
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
    status: form.status || 'active',
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
