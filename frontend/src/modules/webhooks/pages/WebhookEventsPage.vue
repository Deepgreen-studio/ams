<template>
  <div>
    <!-- <PageHeader
      title="Webhook Events"
      description="Catalog of system and module events available for webhook subscriptions."
    /> -->
    <WebhookSubnav />

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
      <div v-else-if="!store.events.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No webhook events found.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Event</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Module</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Description</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.events" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.label }}</p>
              <p class="font-mono text-xs text-slate-500">{{ item.name }}</p>
            </td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.source_module }}</td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.status }}</td>
            <td class="px-4 py-3 text-slate-600">{{ item.description || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
onMounted(() => store.fetchEvents({ per_page: 100 }));
</script>
