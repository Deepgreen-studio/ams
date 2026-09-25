<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        v-if="department && can('companies.update')"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="openEdit"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-500" />
        Edit
      </button>
      <button
        v-if="department && can('companies.update')"
        type="button"
        class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
        @click="showDelete = true"
      >
        <TrashIcon class="h-4 w-4 text-white" />
        Delete
      </button>
    </Teleport>

    <div
      v-if="departmentsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ departmentsStore.error }}
    </div>

    <div
      v-if="departmentsStore.loading && !department"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="department" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</p>
              <h2 class="mt-1 text-2xl font-semibold text-slate-900">{{ departmentName }}</h2>
            </div>
            <StatusBadge :status="department.status" />
          </div>

          <dl class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in detailItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900">
                <RouterLink
                  v-if="item.to"
                  :to="item.to"
                  class="text-brand-700 hover:text-brand-800"
                >
                  {{ item.value }}
                </RouterLink>
                <span v-else>{{ item.value }}</span>
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Note</h3>
          <p class="mt-3 whitespace-pre-wrap text-sm text-slate-700">
            {{ department.note || 'No note added.' }}
          </p>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Teams</h3>
          <ul v-if="teams.length" class="mt-4 space-y-2">
            <li
              v-for="team in teams"
              :key="team.uuid"
              class="flex items-center justify-between gap-3 rounded-[12px] bg-zinc-50 px-4 py-3"
            >
              <span class="text-sm font-medium text-slate-800">{{ team.name }}</span>
              <StatusBadge :status="team.status" />
            </li>
          </ul>
          <p v-else class="mt-4 text-sm text-slate-500">No teams in this department.</p>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Record</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">{{ formatDateTime(department.created_at) || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">{{ formatDateTime(department.updated_at) || '—' }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <div
      v-if="formOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
      @click.self="closeForm"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true">
        <h3 class="text-lg font-semibold text-slate-900">Edit department</h3>
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
      :open="showDelete"
      title="Delete department"
      :message="`Delete ${departmentName || 'this department'}?`"
      :loading="departmentsStore.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useDepartmentsStore } from '@/modules/companies/stores/companies';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import { formatDateTime } from '@/utils/formatters';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { can } = usePermissions();
const departmentsStore = useDepartmentsStore();
const companies = ref([]);
const formOpen = ref(false);
const formError = ref('');
const showDelete = ref(false);
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

const department = computed(() => departmentsStore.currentDepartment);
const departmentName = computed(
  () => department.value?.department_name || department.value?.name || '',
);
const teams = computed(() => department.value?.teams || []);
const companyOptions = computed(() =>
  companies.value.map((company) => ({
    value: company.uuid,
    label: company.company_name || company.legal_name || company.uuid,
  })),
);
const detailItems = computed(() => {
  const current = department.value;
  if (!current) return [];

  const company = current.company;

  return [
    {
      label: 'Company',
      value: company?.company_name || '—',
      to: company?.uuid ? { name: 'companies.show', params: { id: company.uuid } } : null,
    },
    { label: 'Description', value: current.description || '—' },
    { label: 'Teams', value: String(teams.value.length) },
  ];
});

watch(
  () => route.params.id,
  () => {
    load();
  },
);

onMounted(async () => {
  await Promise.all([load(), loadCompanies()]);
});

async function load() {
  const id = route.params.id;
  if (!id) return;
  if (departmentsStore.currentDepartment?.uuid !== id) {
    departmentsStore.currentDepartment = null;
  }
  try {
    await departmentsStore.fetchDepartment(id);
  } catch {
    // Store already records the error.
  }
}

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

function openEdit() {
  const current = department.value;
  if (!current) return;
  form.department_name = current.department_name || current.name || '';
  form.company_id = current.company?.uuid || '';
  form.note = current.note || '';
  form.status = current.status || 'active';
  formError.value = '';
  formOpen.value = true;
}

function closeForm() {
  if (departmentsStore.saving) return;
  formOpen.value = false;
}

async function onSave() {
  formError.value = '';
  if (!form.department_name.trim() || !form.company_id) {
    formError.value = 'Department name and company are required.';
    return;
  }

  try {
    await departmentsStore.updateDepartment(route.params.id, {
      department_name: form.department_name.trim(),
      company_id: form.company_id,
      note: form.note.trim() || null,
      status: form.status || 'active',
    });
    toast.success('Department updated successfully.');
    formOpen.value = false;
    await load();
  } catch (err) {
    formError.value = err?.message || 'Unable to save department.';
  }
}

async function confirmDelete() {
  try {
    await departmentsStore.deleteDepartment(route.params.id);
    toast.success('Department deleted successfully.');
    showDelete.value = false;
    departmentsStore.currentDepartment = null;
    await router.push({ name: 'departments.index' });
  } catch (err) {
    toast.error(err?.message || 'Unable to delete department.');
  }
}
</script>
