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
        <div class="space-y-3">
          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <input
              v-model="form.branch_name"
              type="text"
              placeholder="Branch name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <input
              v-model="form.city"
              type="text"
              placeholder="City"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <input
              v-model="form.country"
              type="text"
              placeholder="Country"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <SelectBox
              v-model="form.status"
              wrapper-class="w-full"
              :options="statusOptions"
            />
          </div>
          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1.2fr_auto_auto]">
            <input
              v-model="form.address"
              type="text"
              placeholder="Address (optional)"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <input
              v-model="form.phone"
              type="text"
              placeholder="Phone"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <input
              v-model="form.email"
              type="email"
              placeholder="Email"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <button
              type="button"
              class="h-10 w-full rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60 xl:w-auto"
              :disabled="saving || !form.branch_name.trim()"
              @click="onSave"
            >
              {{ submitLabel }}
            </button>
            <button
              v-if="editingId"
              type="button"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50 xl:w-auto"
              @click="cancelEdit"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>

      <LocationTable
        :locations="locationsStore.locations"
        :loading="locationsStore.loading"
        embedded
        @edit="openEdit"
        @delete="openDelete"
      />

      <div class="border-t border-zinc-100 px-6 py-5 sm:px-8">
        <Pagination
          :meta="locationsStore.meta"
          :loading="locationsStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete location"
      :message="`Delete ${pending?.branch_name || 'this location'}?`"
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
import LocationTable from '@/modules/companies/components/LocationTable.vue';
import { useCompaniesStore, useLocationsStore } from '@/modules/companies/stores/companies';
import { companyService } from '@/modules/companies/services/companyService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const toast = useToast();
const companiesStore = useCompaniesStore();
const locationsStore = useLocationsStore();
const pending = ref(null);
const editingId = ref(null);
const saving = ref(false);
const perPage = ref(10);

const form = reactive({
  branch_name: '',
  address: '',
  city: '',
  country: '',
  phone: '',
  email: '',
  status: 'active',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const submitLabel = computed(() => {
  if (saving.value) return 'Saving...';
  return editingId.value ? 'Update location' : 'Add location';
});

onMounted(async () => {
  await companiesStore.fetchCompany(route.params.id);
  await load();
});

async function load(page = 1) {
  await locationsStore.fetchLocations({
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
  form.branch_name = '';
  form.address = '';
  form.city = '';
  form.country = '';
  form.phone = '';
  form.email = '';
  form.status = 'active';
  editingId.value = null;
}

function openEdit(item) {
  editingId.value = String(item.uuid || item.id || '');
  form.branch_name = item.branch_name || '';
  form.address = item.address || '';
  form.city = item.city || '';
  form.country = item.country || '';
  form.phone = item.phone || '';
  form.email = item.email || '';
  form.status = item.status || 'active';
}

function cancelEdit() {
  resetForm();
}

async function onSave() {
  if (!form.branch_name.trim()) {
    toast.error('Branch name is required.', 'Validation Failed');
    return;
  }

  const payload = {
    branch_name: form.branch_name.trim(),
    address: form.address.trim() ? form.address.trim() : null,
    city: form.city.trim() ? form.city.trim() : null,
    country: form.country.trim() ? form.country.trim() : null,
    phone: form.phone.trim() ? form.phone.trim() : null,
    email: form.email.trim() ? form.email.trim() : null,
    status: form.status || 'active',
  };

  saving.value = true;
  try {
    if (editingId.value) {
      const { data } = await companyService.updateLocation(editingId.value, payload);
      toast.success(data.message || 'Location updated successfully.');
    } else {
      const { data } = await companyService.createLocation({
        company_id: route.params.id,
        ...payload,
      });
      toast.success(data.message || 'Location created successfully.');
    }
    resetForm();
    await load(locationsStore.meta?.current_page || 1);
  } catch (err) {
    toast.error(err?.message || 'Unable to save location.', 'Error');
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
    await companyService.deleteLocation(id);
    toast.success('Location deleted successfully.');
    if (editingId.value === id) resetForm();
    pending.value = null;
    await load();
  } catch (err) {
    toast.error(err?.message || 'Unable to delete location.', 'Error');
    pending.value = null;
  } finally {
    saving.value = false;
  }
}
</script>
