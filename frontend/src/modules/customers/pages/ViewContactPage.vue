<template>
  <div>
    <!-- <PageHeader
      :title="contact?.name || 'Contact details'"
      description="Contact profile and activity timeline."
    >
      <template #actions>
        <template v-if="contact">
          <RouterLink
            :to="{ name: 'customers.contacts', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back to contacts
          </RouterLink>
          <RouterLink
            :to="{
              name: 'customers.contacts.edit',
              params: { id: route.params.id, contactId: contact.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="contact.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="contactsStore.saving"
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
      <template v-if="contact">
          <RouterLink
            :to="{ name: 'customers.contacts', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back to contacts
          </RouterLink>
          <RouterLink
            :to="{
              name: 'customers.contacts.edit',
              params: { id: route.params.id, contactId: contact.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="contact.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="contactsStore.saving"
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
      v-if="contactsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contactsStore.error }}
    </div>

    <div
      v-if="contactsStore.loading && !contact"
      class="h-48 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="contact" class="space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ contact.name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
              {{ contact.email || contact.phone || 'No contact details' }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <ContactTypeBadge :type="contact.contact_type" />
            <StatusBadge :status="contact.status" />
          </div>
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ contact.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Position</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ contact.position || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Department</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ contact.department || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Customer</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ contact.customer?.display_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ contact.creator?.full_name || '—' }}</dd>
          </div>
          <div class="sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Notes</dt>
            <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-900">
              {{ contact.notes || '—' }}
            </dd>
          </div>
        </dl>
      </div>

      <ContactTimeline :items="contactsStore.timeline" :loading="timelineLoading" />
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive contact"
      :message="`Archive ${contact?.name || 'this contact'}?`"
      confirm-label="Archive"
      :loading="contactsStore.saving"
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
import ContactTimeline from '@/modules/customers/components/ContactTimeline.vue';
import ContactTypeBadge from '@/modules/customers/components/ContactTypeBadge.vue';
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import { useCustomerContactsStore } from '@/modules/customers/stores/contacts';

const route = useRoute();
const router = useRouter();
const contactsStore = useCustomerContactsStore();
const showArchive = ref(false);
const timelineLoading = ref(false);

const contact = computed(() => contactsStore.currentContact);

onMounted(async () => {
  await contactsStore.fetchContact(route.params.contactId);
  timelineLoading.value = true;
  try {
    await contactsStore.fetchTimeline(route.params.contactId);
  } finally {
    timelineLoading.value = false;
  }
});

async function confirmArchive() {
  await contactsStore.archiveContact(route.params.contactId);
  showArchive.value = false;
  await router.push({ name: 'customers.contacts', params: { id: route.params.id } });
}

async function restore() {
  await contactsStore.restoreContact(route.params.contactId);
  await contactsStore.fetchContact(route.params.contactId);
  await contactsStore.fetchTimeline(route.params.contactId);
}
</script>
