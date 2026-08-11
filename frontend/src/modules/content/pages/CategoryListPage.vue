<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <button
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="openCreate"
      >
        Create category
      </button>
    </Teleport>

    <ContentSubnav />

    <div
      v-if="taxonomy.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ taxonomy.successMessage }}
    </div>
    <div
      v-if="taxonomy.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ taxonomy.error }}
    </div>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-8 py-6">
        <form
          class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
          @submit.prevent="onFilter"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Name, slug, SEO..."
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.status"
              wrapper-class="min-w-[9.5rem]"
              :options="statusOptions"
            />
            <button
              type="submit"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="onReset"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <div v-if="taxonomy.loading" class="space-y-3 px-8 py-6">
        <div v-for="n in 5" :key="n" class="h-12 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!taxonomy.categories.length"
        title="No categories found"
        description="Create nested CMS categories with SEO slugs, status, and sort order."
        class="px-8 py-6"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="onReset"
          >
            Reset
          </button>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="openCreate"
          >
            Create category
          </button>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Category</th>
              <th
                class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell"
              >
                Parent
              </th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th
                class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell"
              >
                Sort
              </th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in taxonomy.categories"
              :key="item.uuid"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">{{ item.name }}</p>
                <p class="mt-0.5 font-mono text-xs text-slate-400">{{ item.slug }}</p>
              </td>
              <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
                {{ item.parent?.name || '—' }}
              </td>
              <td class="px-5 py-4">
                <StatusBadge :status="item.is_active ? 'active' : 'inactive'" />
              </td>
              <td class="hidden px-5 py-4 text-slate-600 lg:table-cell">{{ item.sort_order }}</td>
              <td class="px-5 py-4">
                <div class="flex justify-end">
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                    :aria-expanded="openMenuId === item.uuid"
                    aria-haspopup="menu"
                    aria-label="Open actions"
                    @click.stop="toggleMenu(item.uuid, $event)"
                  >
                    <EllipsisVerticalIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="border-t border-zinc-100 px-8 py-4">
        <Pagination
          :meta="taxonomy.categoryMeta"
          :loading="taxonomy.loading"
          @change="(page) => taxonomy.fetchCategories({ page })"
        />
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="openMenuId && activeItem"
        class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
        role="menu"
        :style="menuStyle"
        @click.stop
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
          role="menuitem"
          @click="onMenuEdit(activeItem)"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-400" />
          Edit
        </button>
        <button
          type="button"
          class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
          role="menuitem"
          @click="onMenuDelete(activeItem)"
        >
          <TrashIcon class="h-4 w-4 text-red-500" />
          Delete
        </button>
      </div>
    </Teleport>

    <div
      v-if="showForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
      @click.self="closeForm"
    >
      <div
        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[12px] bg-white shadow-2xl ring-1 ring-zinc-100"
        role="dialog"
        aria-modal="true"
        :aria-label="editing ? 'Edit category' : 'Create category'"
      >
        <div
          class="sticky top-0 z-10 flex items-start justify-between border-b border-zinc-100 bg-white px-6 py-4"
        >
          <div>
            <h3 class="text-lg font-semibold text-slate-900">
              {{ editing ? 'Edit category' : 'Create category' }}
            </h3>
            <p class="mt-0.5 text-sm text-slate-500">
              {{
                editing
                  ? 'Update hierarchy, status, and SEO metadata.'
                  : 'Add a nested CMS category with optional SEO fields.'
              }}
            </p>
          </div>
          <button
            type="button"
            class="rounded-[12px] p-1.5 text-slate-400 hover:bg-zinc-100 hover:text-slate-600"
            aria-label="Close"
            @click="closeForm"
          >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
              />
            </svg>
          </button>
        </div>
        <div class="px-6 py-5">
          <CategoryForm
            :initial="editing || {}"
            :parent-options="taxonomy.categories"
            :loading="taxonomy.saving"
            :errors="taxonomy.fieldErrors"
            :error="taxonomy.error || ''"
            :submit-label="editing ? 'Save changes' : 'Create category'"
            @submit="saveCategory"
            @cancel="closeForm"
          />
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete category"
      :message="`Soft delete ${pendingDelete?.name || 'this category'}?`"
      confirm-label="Delete"
      :loading="taxonomy.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import {
  EllipsisVerticalIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import StatusBadge from '@/modules/users/components/StatusBadge.vue';
import CategoryForm from '@/modules/content/components/CategoryForm.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useTaxonomyStore } from '@/modules/content/stores/taxonomy';

const taxonomy = useTaxonomyStore();
const showForm = ref(false);
const editing = ref(null);
const pendingDelete = ref(null);
const filters = reactive({ search: '', status: '' });
const openMenuId = ref(null);
const menuStyle = ref({});

const statusOptions = [
  { value: '', label: 'Status: All' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const activeItem = computed(
  () => taxonomy.categories.find((item) => item.uuid === openMenuId.value) || null,
);

onMounted(() => {
  taxonomy.fetchCategories();
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});

function onFilter() {
  taxonomy.fetchCategories({ ...filters, page: 1 });
}

function onReset() {
  filters.search = '';
  filters.status = '';
  taxonomy.fetchCategories({ search: '', status: '', page: 1 });
}

function openCreate() {
  editing.value = null;
  showForm.value = true;
}

function openEdit(item) {
  editing.value = item;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editing.value = null;
}

async function saveCategory(payload) {
  if (editing.value) {
    await taxonomy.updateCategory(editing.value.uuid, payload);
  } else {
    await taxonomy.createCategory(payload);
  }
  closeForm();
  await taxonomy.fetchCategories();
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  await taxonomy.deleteCategory(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await taxonomy.fetchCategories();
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 96;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onMenuEdit(item) {
  closeMenu();
  openEdit(item);
}

function onMenuDelete(item) {
  closeMenu();
  openDelete(item);
}
</script>
