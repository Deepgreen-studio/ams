<template>
  <div>
    <!-- <PageHeader
      title="Notification center"
      description="Regulator, customer, internal, and affected-user breach notices."
    /> -->
    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.notifications.length"
        title="No notifications"
        description="Breach notifications will appear here once drafted or sent."
      />
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Breach</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Recipient</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Sent</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in store.notifications" :key="item.uuid" class="hover:bg-slate-50/80">
              <td class="px-4 py-3">
                <RouterLink
                  v-if="item.data_breach?.uuid"
                  :to="{ name: 'compliance.breaches.show', params: { id: item.data_breach.uuid } }"
                  class="font-medium text-brand-700 hover:underline"
                >
                  {{ item.data_breach.breach_number }}
                </RouterLink>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-3 text-slate-600">
                {{ item.notification_type_label || item.notification_type }}
              </td>
              <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ item.recipient }}</td>
              <td class="px-4 py-3 text-slate-900">{{ item.status_label || item.status }}</td>
              <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
                {{ formatDate(item.sent_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-4">
      <Pagination
        :meta="store.notificationsMeta"
        :loading="store.loading"
        @change="(page) => store.fetchNotifications({ page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useDataBreachStore();

onMounted(() => {
  store.fetchNotifications({ per_page: 20, page: 1 });
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
