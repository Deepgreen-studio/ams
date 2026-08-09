<template>
  <div>
    <!-- <PageHeader
      title="Connection History"
      description="Outbound requests executed by the API Connection Engine for this integration."
    /> -->
    <IntegrationSubnav v-if="route.params.id" :integration-id="route.params.id" />

    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <div
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
    >
      <div class="flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="filters.search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="URL or error..."
        />
      </div>
      <div class="w-full md:w-44">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Type</label
        >
        <select
          v-model="filters.request_type"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="connection_test">Connection test</option>
          <option value="authentication_test">Authentication test</option>
          <option value="request">Request</option>
          <option value="upload">Upload</option>
          <option value="download">Download</option>
        </select>
      </div>
      <button
        type="button"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        @click="load"
      >
        Filter
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="integrationsStore.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <div
        v-else-if="!integrationsStore.history.length"
        class="px-6 py-12 text-center text-sm text-slate-500"
      >
        No connection history yet.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">When</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Request</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Duration</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="item in integrationsStore.history"
              :key="item.uuid"
              class="cursor-pointer hover:bg-slate-50/80"
              @click="selected = item"
            >
              <td class="px-4 py-3 text-slate-600">{{ formatDate(item.created_at) }}</td>
              <td class="px-4 py-3 text-slate-700">{{ item.request_type }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-slate-900">{{ item.method }}</p>
                <p class="max-w-md truncate text-xs text-slate-500">{{ item.url }}</p>
              </td>
              <td class="px-4 py-3">
                <span :class="item.success ? 'text-emerald-700' : 'text-rose-700'">
                  {{ item.response_status || '—' }} · {{ item.success ? 'OK' : 'Fail' }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ item.duration_ms }} ms</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Pagination
      :meta="integrationsStore.historyMeta"
      :loading="integrationsStore.loading"
      @change="onPage"
    />

    <div v-if="selected" class="mt-4 space-y-4">
      <ResponseViewer
        :response="{
          successful: selected.success,
          status_code: selected.response_status || 0,
          headers: selected.response_headers || {},
          body: tryParse(selected.response_body),
          raw_body: selected.response_body,
          duration_ms: selected.duration_ms,
          attempts: selected.attempts,
          error: selected.error_message,
        }"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import ResponseViewer from '@/modules/integrations/components/ResponseViewer.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const integrationsStore = useIntegrationsStore();
const selected = ref(null);
const filters = reactive({
  search: '',
  request_type: '',
  page: 1,
  per_page: 10,
});

onMounted(() => {
  load();
});

function load() {
  selected.value = null;
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  integrationsStore.fetchHistory(route.params.id, params);
}

function onPage(page) {
  filters.page = page;
  load();
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function tryParse(value) {
  if (!value) return null;
  try {
    return JSON.parse(value);
  } catch {
    return value;
  }
}
</script>
