<template>
  <div>
    <PageHeader
      title="Categories"
      description="Manage unlimited nested CMS categories with SEO slugs, status, and sort order."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          Create category
        </button>
      </template>
    </PageHeader>

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

    <form
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:flex-row lg:flex-wrap lg:items-end"
      @submit.prevent="onFilter"
    >
      <div class="min-w-[12rem] flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="filters.search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="Name, slug, SEO..."
        />
      </div>
      <div class="w-full lg:w-36">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Status</label
        >
        <select
          v-model="filters.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="flex gap-2">
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Filter
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="onReset"
        >
          Reset
        </button>
      </div>
    </form>

    <div
      v-if="taxonomy.selectedCategoryIds.length"
      class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-3 text-sm"
    >
      <span class="text-slate-600">{{ taxonomy.selectedCategoryIds.length }} selected</span>
      <button
        type="button"
        class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700"
        :disabled="taxonomy.saving"
        @click="runBulk('activate')"
      >
        Activate
      </button>
      <button
        type="button"
        class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"
        :disabled="taxonomy.saving"
        @click="runBulk('deactivate')"
      >
        Deactivate
      </button>
      <button
        type="button"
        class="rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700"
        :disabled="taxonomy.saving"
        @click="runBulk('delete')"
      >
        Delete
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="taxonomy.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left">
              <input type="checkbox" :checked="allSelected" @change="toggleAll" />
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Category</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Parent
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Sort
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in taxonomy.categories" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <input
                type="checkbox"
                :checked="taxonomy.selectedCategoryIds.includes(item.uuid)"
                @change="taxonomy.toggleCategorySelection(item.uuid)"
              />
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.slug }}</p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.parent?.name || '—' }}
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-md px-2 py-0.5 text-xs font-medium"
                :class="
                  item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
                "
              >
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ item.sort_order }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  @click="openEdit(item)"
                >
                  Edit
                </button>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="openDelete(item)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <Pagination
        :meta="taxonomy.categoryMeta"
        :loading="taxonomy.loading"
        @change="(page) => taxonomy.fetchCategories({ page })"
      />
    </div>

    <div
      v-if="showForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
      @click.self="closeForm"
    >
      <div
        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl"
        role="dialog"
        aria-modal="true"
        :aria-label="editing ? 'Edit category' : 'Create category'"
      >
        <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white px-6 py-4">
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
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
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
import { computed, onMounted, reactive, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CategoryForm from '@/modules/content/components/CategoryForm.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useTaxonomyStore } from '@/modules/content/stores/taxonomy';

const taxonomy = useTaxonomyStore();
const showForm = ref(false);
const editing = ref(null);
const pendingDelete = ref(null);
const filters = reactive({ search: '', status: '' });

const allSelected = computed(() => {
  const ids = taxonomy.categories.map((item) => item.uuid);
  return ids.length > 0 && ids.every((id) => taxonomy.selectedCategoryIds.includes(id));
});

onMounted(() => {
  taxonomy.fetchCategories();
});

function onFilter() {
  taxonomy.fetchCategories({ ...filters, page: 1 });
}

function onReset() {
  filters.search = '';
  filters.status = '';
  taxonomy.fetchCategories({ search: '', status: '', page: 1 });
}

function toggleAll() {
  taxonomy.toggleSelectAllCategories(taxonomy.categories.map((item) => item.uuid));
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

async function runBulk(action) {
  await taxonomy.bulkCategories(action);
  await taxonomy.fetchCategories();
}
</script>
