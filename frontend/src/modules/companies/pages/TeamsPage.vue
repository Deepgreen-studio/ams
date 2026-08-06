<template>
  <div>
    <PageHeader title="Teams" :description="`Manage teams for ${companyName}.`">
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
      v-if="teamsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ teamsStore.error }}
    </div>
    <div
      v-if="teamsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ teamsStore.successMessage }}
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
      <h3 class="mb-4 text-sm font-semibold text-slate-900">Add team</h3>
      <form class="grid gap-4 md:grid-cols-4" @submit.prevent="onCreate">
        <select
          v-model="form.department_id"
          required
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="" disabled>Department</option>
          <option v-for="dept in departmentsStore.departments" :key="dept.uuid" :value="dept.uuid">
            {{ dept.name }}
          </option>
        </select>
        <input
          v-model="form.name"
          type="text"
          required
          placeholder="Team name"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <input
          v-model="form.description"
          type="text"
          placeholder="Description"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="teamsStore.saving"
        >
          {{ teamsStore.saving ? 'Saving...' : 'Add' }}
        </button>
      </form>
    </div>

    <TeamTable :teams="teamsStore.teams" :loading="teamsStore.loading" @delete="openDelete" />
    <div class="mt-4">
      <Pagination
        :meta="teamsStore.meta"
        :loading="teamsStore.loading"
        @change="(page) => load(page)"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete team"
      :message="`Delete ${pending?.name || 'this team'}?`"
      :loading="teamsStore.saving"
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
import TeamTable from '@/modules/companies/components/TeamTable.vue';
import {
  useCompaniesStore,
  useDepartmentsStore,
  useTeamsStore,
} from '@/modules/companies/stores/companies';

const route = useRoute();
const companiesStore = useCompaniesStore();
const departmentsStore = useDepartmentsStore();
const teamsStore = useTeamsStore();
const pending = ref(null);
const form = reactive({ department_id: '', name: '', description: '', status: 'active' });
const companyName = computed(() => companiesStore.currentCompany?.company_name || 'company');

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await departmentsStore.fetchDepartments({ company: route.params.id, per_page: 100 });
  await load();
});

async function load(page = 1) {
  await teamsStore.fetchTeams({ company: route.params.id, page, per_page: 10 });
}

async function onCreate() {
  await teamsStore.createTeam({
    company_id: route.params.id,
    department_id: form.department_id,
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
  await teamsStore.deleteTeam(pending.value.uuid);
  pending.value = null;
  await load();
}
</script>
