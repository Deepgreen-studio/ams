<template>
  <div>
    <!-- <PageHeader
      title="Webhook Logs"
      description="Delivery history, statuses, and failed webhook retries."
    /> -->
    <WebhookSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
      @submit.prevent="load"
    >
      <div class="flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="filters.search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Status</label
        >
        <select
          v-model="filters.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="success">Success</option>
          <option value="failed">Failed</option>
          <option value="retrying">Retrying</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Direction</label
        >
        <select
          v-model="filters.direction"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="outgoing">Outgoing</option>
          <option value="incoming">Incoming</option>
        </select>
      </div>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <div v-else-if="!store.logs.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No webhook logs found.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Webhook</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Event</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.logs" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.webhook?.name || '—' }}</p>
              <p class="text-xs capitalize text-slate-500">
                {{ item.direction }} · {{ item.response_status || '—' }}
              </p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.event_name || '—' }}</td>
            <td
              class="px-4 py-3 capitalize"
              :class="
                item.status === 'success'
                  ? 'text-emerald-700'
                  : item.status === 'failed' || item.status === 'retrying'
                    ? 'text-rose-700'
                    : 'text-slate-700'
              "
            >
              {{ item.status }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  @click="selected = item"
                >
                  View
                </button>
                <button
                  v-if="['failed', 'retrying'].includes(item.status)"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-60"
                  :disabled="store.saving"
                  @click="retry(item.uuid)"
                >
                  Retry
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :meta="store.logsMeta" :loading="store.loading" @change="onPage" />

    <div v-if="selected" class="mt-4 rounded-xl border border-slate-200 bg-white p-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Log detail</h3>
        <button
          type="button"
          class="text-xs text-slate-500 hover:text-slate-800"
          @click="selected = null"
        >
          Close
        </button>
      </div>
      <pre class="max-h-96 overflow-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{
        JSON.stringify(selected, null, 2)
      }}</pre>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
const selected = ref(null);
const filters = reactive({ search: '', status: '', direction: '', page: 1, per_page: 10 });

onMounted(() => load());

function load() {
  selected.value = null;
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchLogs(params);
}

function onPage(page) {
  filters.page = page;
  load();
}

async function retry(id) {
  await store.retryLog(id);
  await load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}
</script>
