<template>
  <div>
    <!-- <PageHeader
      :title="customer?.display_name || 'Customer details'"
      description="Customer profile and relationship overview."
    >
      <template #actions>
        <template v-if="customer">
          <RouterLink
            :to="{ name: 'customers.edit', params: { id: customer.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="customer.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="customersStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="customer">
          <RouterLink
            :to="{ name: 'customers.edit', params: { id: customer.uuid } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="customer.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="customersStore.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
    </Teleport>

    <div
      v-if="customersStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ customersStore.error }}
    </div>

    <div
      v-if="customersStore.loading && !customer"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="customer" class="space-y-6">
      <CustomerCard :customer="customer" />

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          :to="{ name: 'customers.contacts', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Contacts</p>
          <p class="mt-2 text-sm text-slate-700">
            Manage primary, technical, billing, support, and emergency contacts.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.applications', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Applications</p>
          <p class="mt-2 text-sm text-slate-700">
            Assign applications, environments, ownership, and integrations.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.subscriptions', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subscriptions</p>
          <p class="mt-2 text-sm text-slate-700">
            Plans, renewals, payment status, and Stripe-ready billing.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.licenses', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Licenses</p>
          <p class="mt-2 text-sm text-slate-700">
            Issue, revoke, and review license keys and activation history.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.documents', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Documents</p>
          <p class="mt-2 text-sm text-slate-700">
            Contracts, NDAs, invoices, certificates, and file versions.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.communications', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Communications</p>
          <p class="mt-2 text-sm text-slate-700">
            Notes, tasks, reminders, email history, and call logs.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.analytics', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Analytics</p>
          <p class="mt-2 text-sm text-slate-700">
            Health score, usage charts, risk indicators, and growth trends.
          </p>
        </RouterLink>
        <RouterLink
          :to="{ name: 'customers.edit', params: { id: customer.uuid } }"
          class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-300"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Edit profile</p>
          <p class="mt-2 text-sm text-slate-700">Update customer identity, status, and notes.</p>
        </RouterLink>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
          Profile details
        </h3>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs text-slate-500">First name</dt>
            <dd class="text-sm text-slate-900">{{ customer.first_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Last name</dt>
            <dd class="text-sm text-slate-900">{{ customer.last_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Customer company name</dt>
            <dd class="text-sm text-slate-900">{{ customer.company_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Created by</dt>
            <dd class="text-sm text-slate-900">{{ customer.creator?.full_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Updated by</dt>
            <dd class="text-sm text-slate-900">{{ customer.updater?.full_name || '—' }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Notes</dt>
            <dd class="text-sm text-slate-900 whitespace-pre-wrap">{{ customer.notes || '—' }}</dd>
          </div>
        </dl>
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
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CustomerCard from '@/modules/customers/components/CustomerCard.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';

const route = useRoute();
const router = useRouter();
const customersStore = useCustomersStore();
const showArchive = ref(false);

const customer = computed(() => customersStore.currentCustomer);

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
