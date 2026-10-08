<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'webhooks.index' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to webhooks
      </RouterLink>
    </Teleport>

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

    <WebhookTable
      :webhooks="store.webhooks"
      :loading="store.loading"
      @restore="confirmRestore"
      @force-delete="openForceDelete"
    >
      <template #toolbar>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              placeholder="Search deleted webhooks..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @input="onSearchInput"
              @search="onSearchInput"
            />
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applySearch"
            >
              Apply Filter
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetSearch"
            >
              Reset Filter
            </button>
          </div>
        </div>
      </template>

      <template #footer>
        <Pagination
          :meta="store.meta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </WebhookTable>

    <DeleteConfirmation
      :open="Boolean(pendingForceDelete)"
      title="Permanently delete webhook"
      :message="`Permanently delete ${pendingForceDelete?.name || 'this webhook'}? This cannot be undone.`"
      confirm-label="Permanent Delete"
      :loading="store.saving"
      @cancel="pendingForceDelete = null"
      @confirm="confirmForceDelete"
    />
  </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import WebhookTable from '@/modules/webhooks/components/WebhookTable.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
const toast = useToast();
const search = ref('');
const pendingForceDelete = ref(null);
const previousFilters = ref(null);
let searchTimer = null;

onMounted(() => {
  previousFilters.value = { ...store.filters };
  loadTrash();
});

onBeforeUnmount(() => {
  window.clearTimeout(searchTimer);
  if (previousFilters.value) {
    store.filters = {
      ...previousFilters.value,
      trashed: previousFilters.value.trashed === 'only' ? '' : previousFilters.value.trashed,
    };
  }
});

function loadTrash(overrides = {}) {
  return store.fetchWebhooks({
    trashed: 'only',
    status: '',
    direction: '',
    company: '',
    search: search.value.trim(),
    sort_by: 'deleted_at',
    sort_dir: 'desc',
    page: 1,
    ...overrides,
  });
}

function applySearch() {
  loadTrash({ search: search.value.trim(), page: 1 });
}

function resetSearch() {
  search.value = '';
  loadTrash({ search: '', page: 1 });
}

function onSearchInput() {
  window.clearTimeout(searchTimer);
  const delay = search.value.trim() ? 300 : 0;
  searchTimer = window.setTimeout(() => applySearch(), delay);
}

function onPageChange(page) {
  loadTrash({ page, sort_by: store.filters.sort_by, sort_dir: store.filters.sort_dir });
}

function onPerPageChange(perPage) {
  loadTrash({
    per_page: perPage,
    page: 1,
    sort_by: store.filters.sort_by,
    sort_dir: store.filters.sort_dir,
  });
}

function openForceDelete(webhook) {
  pendingForceDelete.value = webhook;
}

async function confirmForceDelete() {
  if (!pendingForceDelete.value) {
    return;
  }

  const name = pendingForceDelete.value.name || 'Webhook';

  try {
    const data = await store.forceDeleteWebhook(pendingForceDelete.value.uuid);
    pendingForceDelete.value = null;
    toast.success(data?.message || `${name} permanently deleted.`, 'Webhook deleted');
    await loadTrash({
      page: store.filters.page,
      sort_by: store.filters.sort_by,
      sort_dir: store.filters.sort_dir,
    });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to permanently delete webhook.', 'Delete failed');
  }
}

async function confirmRestore(webhook) {
  const name = webhook?.name || 'Webhook';

  try {
    const data = await store.restoreWebhook(webhook.uuid);
    toast.success(data?.message || `${name} restored.`, 'Webhook restored');
    await loadTrash({
      page: store.filters.page,
      sort_by: store.filters.sort_by,
      sort_dir: store.filters.sort_dir,
    });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to restore webhook.', 'Restore failed');
  }
}
</script>
