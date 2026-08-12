<template>
  <div>
    <WebhookSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <WebhookEventsTable
      :events="store.events"
      :loading="store.loading"
      @view="openView"
    >
      <template #toolbar>
        <EventSearchFilters v-model="filters" @submit="onFilter" @reset="onReset" />
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
          :meta="store.eventsMeta"
          :loading="store.loading"
          @change="onPage"
          @per-page="onPerPage"
        />
      </template>
    </WebhookEventsTable>

    <DetailModal
      :open="Boolean(selected)"
      :title="selected?.label || 'Event detail'"
      :subtitle="selected?.name || ''"
      @close="selected = null"
    >
      <div v-if="selected" class="space-y-4">
        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Module</dt>
            <dd class="mt-1.5 capitalize text-sm text-slate-800">
              {{ String(selected.source_module || '—').replaceAll('_', ' ') }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1.5">
              <StatusBadge :status="selected.status" />
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt>
            <dd class="mt-1.5 text-sm text-slate-700">{{ selected.description || '—' }}</dd>
          </div>
        </dl>

        <div v-if="selected.payload_schema">
          <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">
            Payload schema
          </p>
          <pre
            class="max-h-[50vh] overflow-auto rounded-[12px] bg-slate-900 p-4 text-xs text-slate-100"
          >{{ formatSchema(selected.payload_schema) }}</pre>
        </div>
      </div>
    </DetailModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import DetailModal from '@/modules/webhooks/components/DetailModal.vue';
import EventSearchFilters from '@/modules/webhooks/components/EventSearchFilters.vue';
import StatusBadge from '@/modules/webhooks/components/StatusBadge.vue';
import WebhookEventsTable from '@/modules/webhooks/components/WebhookEventsTable.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
const selected = ref(null);
const filters = reactive({
  search: '',
  source_module: '',
  status: '',
  page: 1,
  per_page: 20,
});

onMounted(() => load());

function load() {
  const params = Object.fromEntries(
    Object.entries(filters).filter(([, v]) => v !== '' && v != null),
  );
  store.fetchEvents(params);
}

function openView(event) {
  selected.value = event;
}

function onFilter(next) {
  Object.assign(filters, next, { page: 1 });
  selected.value = null;
  load();
}

function onReset() {
  filters.search = '';
  filters.source_module = '';
  filters.status = '';
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

function formatSchema(schema) {
  if (typeof schema === 'string') return schema;
  try {
    return JSON.stringify(schema, null, 2);
  } catch {
    return String(schema);
  }
}
</script>
