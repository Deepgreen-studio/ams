<template>
  <div>
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

    <WebhookLogsTable
      :logs="store.logs"
      :loading="store.loading"
      :retrying="store.saving"
      @view="openView"
      @retry="retry"
    >
      <template #toolbar>
        <LogSearchFilters v-model="filters" @submit="onFilter" @reset="onReset" />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
      </template>

      <template #footer>
        <Pagination
          :meta="store.logsMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </template>
    </WebhookLogsTable>

    <DetailModal
      :open="Boolean(selected)"
      title="Log detail"
      :subtitle="selected ? `${selected.webhook?.name || 'Webhook'} · ${selected.event_name || '—'}` : ''"
      @close="selected = null"
    >
      <div v-if="selected" class="space-y-4">
        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1.5">
              <StatusBadge :status="selected.status" kind="delivery" />
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">When</dt>
            <dd class="mt-1.5 text-sm text-slate-800">
              {{ formatDate(selected.created_at) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Direction</dt>
            <dd class="mt-1.5 capitalize text-sm text-slate-800">
              {{ selected.direction || '—' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
              Response status
            </dt>
            <dd class="mt-1.5 text-sm text-slate-800">
              {{ selected.response_status || '—' }}
            </dd>
          </div>
        </dl>

        <div>
          <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Payload</p>
          <pre
            class="max-h-[50vh] overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs text-slate-100"
          >{{ formatJson(selected) }}</pre>
        </div>
      </div>
    </DetailModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DetailModal from '@/modules/webhooks/components/DetailModal.vue';
import LogSearchFilters from '@/modules/webhooks/components/LogSearchFilters.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import WebhookLogsTable from '@/modules/webhooks/components/WebhookLogsTable.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
const selected = ref(null);
const filters = reactive({ search: '', status: '', direction: '', page: 1, per_page: 10 });

onMounted(() => load());

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchLogs(params);
}

function openView(log) {
  selected.value = log;
}

function onFilter(next) {
  Object.assign(filters, next, { page: 1 });
  selected.value = null;
  load();
}

function onReset() {
  filters.search = '';
  filters.status = '';
  filters.direction = '';
  filters.page = 1;
  selected.value = null;
  load();
}

function onPage(page) {
  filters.page = page;
  selected.value = null;
  load();
}

function onPerPage(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  selected.value = null;
  load();
}

async function retry(log) {
  await store.retryLog(log.uuid);
  await load();
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString() : '—';
}

function formatJson(value) {
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}
</script>
