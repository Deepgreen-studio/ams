<template>
  <div>
    <IntegrationSubnav v-if="route.params.id" :integration-id="route.params.id" />

    <div
      v-if="integrationsStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ integrationsStore.error }}
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-8 py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="URL or error..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.request_type"
              wrapper-class="min-w-[11rem]"
              :options="typeOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="integrationsStore.loading" class="space-y-3 px-8 py-6">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
      </div>

      <div
        v-else-if="!integrationsStore.history.length"
        class="px-8 py-12 text-center text-sm text-slate-500"
      >
        No connection history yet.
      </div>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">When</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Request</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Duration</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in integrationsStore.history"
              :key="item.uuid"
              class="cursor-pointer border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
              :class="selected?.uuid === item.uuid ? 'bg-brand-50/40' : ''"
              @click="selected = item"
            >
              <td class="px-5 py-4 text-slate-600">{{ formatDate(item.created_at) }}</td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
                >
                  {{ formatType(item.request_type) }}
                </span>
              </td>
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">{{ item.method }}</p>
                <p class="max-w-md truncate text-xs text-slate-500">{{ item.url }}</p>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full border bg-white px-2.5 py-1 text-xs font-medium"
                  :class="
                    item.success
                      ? 'border-emerald-600 text-emerald-700'
                      : 'border-rose-500 text-rose-700'
                  "
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="item.success ? 'bg-emerald-600' : 'bg-rose-500'"
                  />
                  {{ item.response_status || '—' }} · {{ item.success ? 'OK' : 'Fail' }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ item.duration_ms }} ms</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="integrationsStore.historyMeta"
        class="border-t border-zinc-100 px-8 py-5"
      >
        <Pagination
          :meta="integrationsStore.historyMeta"
          :loading="integrationsStore.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </div>
    </div>

    <div v-if="selected" class="mt-6">
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
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import IntegrationSubnav from '@/modules/integrations/components/IntegrationSubnav.vue';
import ResponseViewer from '@/modules/integrations/components/ResponseViewer.vue';
import { useIntegrationsStore } from '@/modules/integrations/stores/integrations';

const route = useRoute();
const integrationsStore = useIntegrationsStore();
const selected = ref(null);

const typeOptions = [
  { value: '', label: 'Type: All' },
  { value: 'connection_test', label: 'Connection test' },
  { value: 'authentication_test', label: 'Authentication test' },
  { value: 'request', label: 'Request' },
  { value: 'upload', label: 'Upload' },
  { value: 'download', label: 'Download' },
];

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

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.request_type = '';
  filters.page = 1;
  load();
}

function onPage(page) {
  filters.page = page;
  load();
}

function onPerPage(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatType(value) {
  return String(value || '—')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
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
