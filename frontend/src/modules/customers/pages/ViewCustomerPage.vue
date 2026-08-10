<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="customer">
        <RouterLink
          :to="{ name: 'customers.edit', params: { id: customer.uuid } }"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </RouterLink>
        <button
          v-if="customer.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="customersStore.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showArchive = true"
        >
          <ArchiveBoxIcon class="h-4 w-4 text-white" />
          Archive
        </button>
      </template>
    </Teleport>

    <div
      v-if="customersStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ customersStore.error }}
    </div>

    <div
      v-if="customersStore.loading && !customer"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="customer" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <CustomerCard :customer="customer" />

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">Profile details</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in profileItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900 whitespace-pre-wrap">
                {{ item.value }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Modules</h3>
          <div class="mt-4 space-y-2.5">
            <RouterLink
              v-for="item in moduleLinks"
              :key="item.to"
              :to="{ name: item.to, params: { id: customer.uuid } }"
              class="flex items-center justify-between gap-3 rounded-[12px] bg-zinc-50 px-4 py-3.5 transition hover:bg-zinc-100"
            >
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] bg-white text-slate-500 ring-1 ring-zinc-100"
                >
                  <component :is="item.icon" class="h-4 w-4" />
                </span>
                <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
              </div>
              <ChevronRightIcon class="h-4 w-4 text-slate-400" />
            </RouterLink>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Details</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Type</dt>
              <dd><TypeBadge :type="customer.customer_type" /></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Status</dt>
              <dd><StatusBadge :status="customer.status || 'active'" /></dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(customer.created_at) || '—' }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(customer.updated_at) || '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive customer"
      :message="`Archive ${customer?.display_name || 'this customer'}?`"
      confirm-label="Archive"
      :loading="customersStore.saving"
      @cancel="showArchive = false"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  ArchiveBoxIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  ChevronRightIcon,
  CreditCardIcon,
  FolderIcon,
  KeyIcon,
  PencilSquareIcon,
  PuzzlePieceIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline';
import { formatDate } from '@/utils/formatters';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CustomerCard from '@/modules/customers/components/CustomerCard.vue';
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import TypeBadge from '@/modules/customers/components/TypeBadge.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const showArchive = ref(false);

const customer = computed(() => customersStore.currentCustomer);

const profileItems = computed(() => [
  { label: 'First name', value: customer.value?.first_name || '—' },
  { label: 'Last name', value: customer.value?.last_name || '—' },
  { label: 'Company name', value: customer.value?.company_name || '—' },
  { label: 'Created by', value: customer.value?.creator?.full_name || '—' },
  { label: 'Updated by', value: customer.value?.updater?.full_name || '—' },
  { label: 'Notes', value: customer.value?.notes || '—' },
]);

const moduleLinks = computed(() => [
  { label: 'Contacts', to: 'customers.contacts', icon: UserGroupIcon },
  { label: 'Applications', to: 'customers.applications', icon: PuzzlePieceIcon },
  { label: 'Subscriptions', to: 'customers.subscriptions', icon: CreditCardIcon },
  { label: 'Licenses', to: 'customers.licenses', icon: KeyIcon },
  { label: 'Documents', to: 'customers.documents', icon: FolderIcon },
  { label: 'Communications', to: 'customers.communications', icon: ChatBubbleLeftRightIcon },
  { label: 'Analytics', to: 'customers.analytics', icon: ChartBarIcon },
]);

onMounted(() => {
  customersStore.fetchCustomer(route.params.id);
});

async function confirmArchive() {
  await customersStore.archiveCustomer(route.params.id);
  showArchive.value = false;
  await router.push({ name: 'customers.index' });
}

async function restore() {
  await customersStore.restoreCustomer(route.params.id);
  await customersStore.fetchCustomer(route.params.id);
}
</script>
