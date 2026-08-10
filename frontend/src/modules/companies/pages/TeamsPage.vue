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
        Add team
      </button>
    </Teleport>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <TeamTable
        :teams="teamsStore.teams"
        :loading="teamsStore.loading"
        embedded
        @edit="openEdit"
        @delete="openDelete"
      />

      <div class="border-t border-zinc-100 px-6 py-5 sm:px-8">
        <Pagination
          :meta="teamsStore.meta"
          :loading="teamsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>

    <TeamFormModal
      :open="formOpen"
      :loading="saving"
      :team="editingTeam"
      :department-options="departmentOptions"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete team"
      :message="`Delete ${pending?.name || 'this team'}?`"
      :loading="saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import TeamFormModal from '@/modules/companies/components/TeamFormModal.vue';
import TeamTable from '@/modules/companies/components/TeamTable.vue';
import {
  useCompaniesStore,
  useDepartmentsStore,
  useTeamsStore,
} from '@/modules/companies/stores/companies';
import { companyService } from '@/modules/companies/services/companyService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const toast = useToast();
const companiesStore = useCompaniesStore();
const departmentsStore = useDepartmentsStore();
const teamsStore = useTeamsStore();
const pending = ref(null);
const editingTeam = ref(null);
const formOpen = ref(false);
const saving = ref(false);
const perPage = ref(10);

const departmentOptions = computed(() =>
  (departmentsStore.departments || []).map((dept) => ({
    value: dept.uuid,
    label: dept.name,
  })),
);

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await departmentsStore.fetchDepartments({ company: route.params.id, per_page: 100 });
  await load();
});

async function load(page = 1) {
  await teamsStore.fetchTeams({
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
  editingTeam.value = null;
  formOpen.value = true;
}

function openEdit(item) {
  editingTeam.value = item;
  formOpen.value = true;
}

function closeForm() {
  if (saving.value) return;
  formOpen.value = false;
  editingTeam.value = null;
}

async function onSave(payload) {
  saving.value = true;
  try {
    const id = editingTeam.value?.uuid || editingTeam.value?.id;
    if (id) {
      const { data } = await companyService.updateTeam(id, payload);
      toast.success(data.message || 'Team updated successfully.');
    } else {
      const { data } = await companyService.createTeam({
        company_id: route.params.id,
        ...payload,
      });
      toast.success(data.message || 'Team created successfully.');
    }
    formOpen.value = false;
    editingTeam.value = null;
    await load(teamsStore.meta?.current_page || 1);
  } catch (err) {
    toast.error(err?.message || 'Unable to save team.', 'Error');
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
    await companyService.deleteTeam(id);
    toast.success('Team deleted successfully.');
    if ((editingTeam.value?.uuid || editingTeam.value?.id) === id) {
      formOpen.value = false;
      editingTeam.value = null;
    }
    pending.value = null;
    await load();
  } catch (err) {
    toast.error(err?.message || 'Unable to delete team.', 'Error');
    pending.value = null;
  } finally {
    saving.value = false;
  }
}
</script>
