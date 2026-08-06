<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!documents.length"
      title="No documents"
      description="Upload contracts, NDAs, invoices, and more."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Document</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Category
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Version
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in documents" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.original_filename }}</p>
            </td>
            <td class="hidden px-4 py-3 capitalize text-slate-600 md:table-cell">
              {{ item.category_label || item.category?.replaceAll('_', '') }}
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">v{{ item.version }}</td>
            <td class="px-4 py-3">
              <DocumentStatusBadge :status="item.status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{
                    name: 'customers.documents.show',
                    params: { id: customerId, documentId: item.uuid },
                  }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  @click="$emit('download', item)"
                >
                  Download
                </button>
                <button
                  v-if="!item.deleted_at"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('archive', item)"
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
import DocumentStatusBadge from '@/modules/customers/components/DocumentStatusBadge.vue';

defineProps({
  documents: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  customerId: { type: String, required: true },
});

defineEmits(['archive', 'download']);
</script>
