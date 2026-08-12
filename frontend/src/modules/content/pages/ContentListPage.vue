<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'content.dashboard' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Dashboard
      </RouterLink>
      <RouterLink
        v-if="can('content.create')"
        :to="{ name: 'content.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create content
      </RouterLink>
    </Teleport>

    <ContentSubnav />

    <div
      v-if="contentStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contentStore.successMessage }}
    </div>
    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div v-if="contentStore.statistics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-8 py-7 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[12px] p-3"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <ContentTable
      :contents="contentStore.contents"
      :loading="contentStore.loading"
      @delete="openDelete"
    >
      <template #toolbar>
        <ContentSearchFilter
          :model-value="contentStore.filters"
          :types="contentStore.types"
          :statuses="contentStore.statuses"
          :categories="contentStore.categories"
          @submit="onFilter"
          @reset="onReset"
        />
      </template>

      <template #empty-action>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="onReset"
        >
          Reset
        </button>
        <RouterLink
          v-if="can('content.create')"
          :to="{ name: 'content.create' }"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create content
        </RouterLink>
      </template>

      <template #footer>
        <Pagination
          :meta="contentStore.meta"
          :loading="contentStore.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </template>
    </ContentTable>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete content"
      :message="`Soft delete ${pendingDelete?.title || 'this content'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="contentStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import {
  ArchiveBoxIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ContentSearchFilter from '@/modules/content/components/ContentSearchFilter.vue';
import ContentTable from '@/modules/content/components/ContentTable.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useContentStore } from '@/modules/content/stores/content';

const contentStore = useContentStore();
const { can } = usePermissions();
const pendingDelete = ref(null);

const statCards = computed(() => [
  {
    label: 'Total',
    value: contentStore.statistics?.total ?? 0,
    icon: DocumentTextIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Published',
    value: contentStore.statistics?.published ?? 0,
    icon: CheckCircleIcon,
    iconBg: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
  },
  {
    label: 'Draft',
    value: contentStore.statistics?.draft ?? 0,
    icon: PencilSquareIcon,
    iconBg: 'bg-slate-100',
    iconColor: 'text-slate-500',
  },
  {
    label: 'Archived',
    value: contentStore.statistics?.archived ?? 0,
    icon: ArchiveBoxIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-600',
  },
  {
    label: 'Trashed',
    value: contentStore.statistics?.trashed ?? 0,
    icon: TrashIcon,
    iconBg: 'bg-rose-50',
    iconColor: 'text-rose-600',
  },
]);

onMounted(async () => {
  await contentStore.fetchCatalog();
  await contentStore.fetchContents();
});

function onFilter(filters) {
  contentStore.fetchContents(filters);
}

function onReset() {
  contentStore.resetFilters();
  contentStore.fetchContents();
}

function onPageChange(page) {
  contentStore.fetchContents({ page });
}

function onPerPageChange(perPage) {
  contentStore.fetchContents({ per_page: perPage, page: 1 });
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await contentStore.deleteContent(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await contentStore.fetchContents();
}
</script>
