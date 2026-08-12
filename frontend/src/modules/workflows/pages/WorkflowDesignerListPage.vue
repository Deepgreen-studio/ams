<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'workflows.designer.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create workflow
      </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search workflows…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.type"
              wrapper-class="min-w-[9.5rem]"
              :options="typeOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[8.5rem]"
              :options="statusOptions"
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

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.workflows.length"
        title="No workflows yet"
        description="Create a workflow definition to start designing approval and business processes."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
          <RouterLink
            :to="{ name: 'workflows.designer.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create workflow
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Workflow</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Type</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Steps</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.workflows"
              :key="item.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="max-w-md px-5 py-4">
                <p class="font-medium text-slate-900">{{ item.name }}</p>
                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">
                  {{ item.description || 'No description' }}
                </p>
              </td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                  {{ item.type_label || item.type }}
                </span>
              </td>
              <td class="px-5 py-4">
                <button
                  type="button"
                  class="rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="item.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-slate-500'"
                  @click="store.toggleWorkflow(item.uuid, !item.is_enabled)"
                >
                  {{ item.is_enabled ? 'Enabled' : 'Disabled' }} · {{ item.status }}
                </button>
              </td>
              <td class="px-5 py-4 text-slate-600">{{ item.steps?.length || 0 }}</td>
              <td class="px-5 py-4">
                <div class="relative flex justify-end">
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                    :aria-expanded="openMenuId === item.uuid"
                    aria-haspopup="menu"
                    aria-label="Open actions"
                    @click.stop="toggleMenu(item.uuid)"
                  >
                    <EllipsisVerticalIcon class="h-5 w-5" />
                  </button>

                  <div
                    v-if="openMenuId === item.uuid"
                    class="absolute right-0 top-10 z-20 w-40 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                    role="menu"
                  >
                    <RouterLink
                      :to="{ name: 'workflows.designer.edit', params: { id: item.uuid } }"
                      class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
                      role="menuitem"
                      @click="closeMenu"
                    >
                      <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                      Edit
                    </RouterLink>
                    <button
                      type="button"
                      class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
                      role="menuitem"
                      @click="start(item)"
                    >
                      <PlayIcon class="h-4 w-4 text-slate-400" />
                      Start
                    </button>
                    <button
                      type="button"
                      class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                      role="menuitem"
                      @click="remove(item)"
                    >
                      <TrashIcon class="h-4 w-4 text-red-500" />
                      Delete
                    </button>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="store.workflowMeta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination
          :meta="store.workflowMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete workflow"
      :message="`Delete “${pendingDelete?.name || 'this workflow'}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="deleting"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import {
  EllipsisVerticalIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlayIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const router = useRouter();
const toast = useToast();
const openMenuId = ref(null);
const pendingDelete = ref(null);
const deleting = ref(false);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

const filters = reactive({
  search: '',
  type: '',
  status: '',
  page: 1,
  per_page: 10,
});

const typeOptions = computed(() => [
  { value: '', label: 'All types' },
  ...store.catalog.types.map((item) => ({ value: item.value, label: item.label })),
]);

const statusOptions = computed(() => [
  { value: '', label: 'All statuses' },
  ...store.catalog.statuses.map((item) => ({ value: item.value, label: item.label })),
]);

async function load() {
  await store.fetchWorkflows({
    search: filters.search || undefined,
    type: filters.type || undefined,
    status: filters.status || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  load();
}

function resetFilters() {
  filters.search = '';
  filters.type = '';
  filters.status = '';
  filters.page = 1;
  load();
}

function onPageChange(page) {
  filters.page = page;
  load();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  load();
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
  openMenuId.value = null;
}

async function start(item) {
  closeMenu();
  const instance = await store.startWorkflow(item.uuid, {
    subject_type: 'manual',
    subject_label: `${item.name} run`,
    context: { compliance_ready: '1' },
  });
  if (instance?.uuid) {
    await router.push({ name: 'workflows.instances.show', params: { id: instance.uuid } });
  }
}

function remove(item) {
  closeMenu();
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;

  deleting.value = true;
  try {
    await store.deleteWorkflow(pendingDelete.value.uuid);
    pendingDelete.value = null;
    if (!store.workflows.length && filters.page > 1) {
      filters.page -= 1;
      await load();
    }
  } finally {
    deleting.value = false;
  }
}

function onDocumentClick() {
  closeMenu();
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  load();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>
