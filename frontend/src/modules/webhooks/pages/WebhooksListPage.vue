<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="can('webhooks.view-trash')"
        :to="{ name: 'webhooks.trash' }"
        class="rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Soft Deleted
      </RouterLink>
      <RouterLink
        v-if="can('webhooks.create')"
        :to="{ name: 'webhooks.create' }"
        class="inline-flex items-center gap-2 rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        <PlusIcon class="h-4 w-4" />
        Create Webhook
      </RouterLink>
    </Teleport>

    <WebhookSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <WebhookTable
      :webhooks="store.webhooks"
      :loading="store.loading"
      @delete="openDelete"
      @restore="confirmRestore"
    >
      <template #toolbar>
        <SearchFilters v-model="filters" @submit="onFilter" @reset="onReset" />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset Filter
        </button>
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
      :open="Boolean(pendingDelete)"
      title="Soft delete webhook"
      :message="`Soft delete ${pendingDelete?.name || 'this webhook'}? It can be restored later.`"
      confirm-label="Soft Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
import { onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SearchFilters from '@/modules/webhooks/components/SearchFilters.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import WebhookTable from '@/modules/webhooks/components/WebhookTable.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const store = useWebhooksStore();
const { can } = usePermissions();
const toast = useToast();
const pendingDelete = ref(null);
const filters = reactive({ search: '', direction: '', status: '' });

onMounted(() => store.fetchWebhooks());

function onFilter(next) {
  Object.assign(filters, next);
  store.fetchWebhooks({ ...filters, page: 1 });
}

function onReset() {
  filters.search = '';
  filters.direction = '';
  filters.status = '';
  store.fetchWebhooks({ page: 1 });
}

function onPageChange(page) {
  store.fetchWebhooks({ page });
}

function onPerPageChange(perPage) {
  store.fetchWebhooks({ per_page: perPage, page: 1 });
}

function openDelete(webhook) {
  pendingDelete.value = webhook;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;

  const name = pendingDelete.value.name || 'Webhook';

  try {
    const data = await store.deleteWebhook(pendingDelete.value.uuid);
    pendingDelete.value = null;
    toast.success(data?.message || `${name} soft deleted.`, 'Webhook soft deleted');
    await store.fetchWebhooks({ ...filters });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to delete webhook.', 'Delete failed');
  }
}

async function confirmRestore(webhook) {
  const name = webhook?.name || 'Webhook';

  try {
    const data = await store.restoreWebhook(webhook.uuid);
    toast.success(data?.message || `${name} restored.`, 'Webhook restored');
    await store.fetchWebhooks({ ...filters });
  } catch (err) {
    toast.error(err?.message || store.error || 'Unable to restore webhook.', 'Restore failed');
  }
}
</script>
