<template>
  <div>
    <PageHeader title="Office locations" :description="`Manage branches for ${companyName}.`">
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
      v-if="locationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ locationsStore.error }}
    </div>
    <div
      v-if="locationsStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ locationsStore.successMessage }}
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
      <h3 class="mb-4 text-sm font-semibold text-slate-900">Add location</h3>
      <form class="grid gap-4 md:grid-cols-3" @submit.prevent="onCreate">
        <input
          v-model="form.branch_name"
          type="text"
          required
          placeholder="Branch name"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <input
          v-model="form.city"
          type="text"
          placeholder="City"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <input
          v-model="form.country"
          type="text"
          placeholder="Country"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <input
          v-model="form.address"
          type="text"
          placeholder="Address"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm md:col-span-2"
        />
        <input
          v-model="form.phone"
          type="text"
          placeholder="Phone"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <input
          v-model="form.email"
          type="email"
          placeholder="Email"
          class="h-12 rounded-[12px] border border-slate-300 px-3 text-sm md:col-span-2"
        />
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="locationsStore.saving"
        >
          {{ locationsStore.saving ? 'Saving...' : 'Add location' }}
        </button>
      </form>
    </div>

    <LocationTable
      :locations="locationsStore.locations"
      :loading="locationsStore.loading"
      @delete="openDelete"
    />
    <div class="mt-4">
      <Pagination
        :meta="locationsStore.meta"
        :loading="locationsStore.loading"
        @change="(page) => load(page)"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete location"
      :message="`Delete ${pending?.branch_name || 'this location'}?`"
      :loading="locationsStore.saving"
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
import LocationTable from '@/modules/companies/components/LocationTable.vue';
import { useCompaniesStore, useLocationsStore } from '@/modules/companies/stores/companies';

const route = useRoute();
const companiesStore = useCompaniesStore();
const locationsStore = useLocationsStore();
const pending = ref(null);
const form = reactive({
  branch_name: '',
  address: '',
  city: '',
  country: '',
  phone: '',
  email: '',
  status: 'active',
});
const companyName = computed(() => companiesStore.currentCompany?.company_name || 'company');

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await load();
});

async function load(page = 1) {
  await locationsStore.fetchLocations({ company: route.params.id, page, per_page: 10 });
}

async function onCreate() {
  await locationsStore.createLocation({
    company_id: route.params.id,
    ...form,
  });
  form.branch_name = '';
  form.address = '';
  form.city = '';
  form.country = '';
  form.phone = '';
  form.email = '';
  await load();
}

function openDelete(item) {
  pending.value = item;
}

async function confirmDelete() {
  await locationsStore.deleteLocation(pending.value.uuid);
  pending.value = null;
  await load();
}
</script>
