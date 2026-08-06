<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!contacts.length"
      title="No contacts found"
      description="Add a contact for this customer."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Contact</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Type
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Position
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="contact in contacts" :key="contact.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ contact.name }}</p>
              <p class="text-xs text-slate-500">{{ contact.email || contact.phone || '—' }}</p>
            </td>
            <td class="hidden px-4 py-3 md:table-cell">
              <ContactTypeBadge :type="contact.contact_type" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ contact.position || '—' }}
              <span v-if="contact.department" class="text-slate-400">
                · {{ contact.department }}</span
              >
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="contact.status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'customers.contacts.show',
                    params: { id: customerId, contactId: contact.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{
                    name: 'customers.contacts.edit',
                    params: { id: customerId, contactId: contact.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('archive', contact)"
                >
                  Archive
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import ContactTypeBadge from '@/modules/customers/components/ContactTypeBadge.vue';

defineProps({
  contacts: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  customerId: { type: String, required: true },
});

defineEmits(['archive']);
</script>
