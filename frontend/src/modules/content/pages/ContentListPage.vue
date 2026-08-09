<template>
  <div>
    <!-- <PageHeader
      title="Content"
      description="Manage headless CMS content for Android, iOS, Web, and future applications."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'content.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create content
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'content.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'content.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
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

    <div v-if="contentStore.statistics" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white px-4 py-3"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="space-y-4">
      <ContentSearchFilter
        :model-value="contentStore.filters"
        :types="contentStore.types"
        :statuses="contentStore.statuses"
        :categories="contentStore.categories"
        @submit="onFilter"
        @reset="onReset"
      />

      <ContentTable
        :contents="contentStore.contents"
        :loading="contentStore.loading"
        @delete="openDelete"
      >
        <template #empty-action>
          <RouterLink
            :to="{ name: 'content.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create content
          </RouterLink>
        </template>
      </ContentTable>

      <Pagination
        :meta="contentStore.meta"
        :loading="contentStore.loading"
        @change="onPageChange"
      />
    </div>

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
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ContentSearchFilter from '@/modules/content/components/ContentSearchFilter.vue';
import ContentTable from '@/modules/content/components/ContentTable.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useContentStore } from '@/modules/content/stores/content';

const contentStore = useContentStore();
const pendingDelete = ref(null);

const statCards = computed(() => [
  { label: 'Total', value: contentStore.statistics?.total ?? 0 },
  { label: 'Published', value: contentStore.statistics?.published ?? 0 },
  { label: 'Draft', value: contentStore.statistics?.draft ?? 0 },
  { label: 'Archived', value: contentStore.statistics?.archived ?? 0 },
  { label: 'Trashed', value: contentStore.statistics?.trashed ?? 0 },
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
