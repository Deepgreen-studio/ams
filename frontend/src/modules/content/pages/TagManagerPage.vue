<template>
  <div>
    <!-- <PageHeader
      title="Tag Manager"
      description="Manage unlimited CMS tags with SEO slugs, status, and sort order."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          Create tag
        </button>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="openCreate"
        >
          Create tag
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
      v-if="taxonomy.selectedTagIds.length"
      class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-3 text-sm"
    >
      <span class="text-slate-600">{{ taxonomy.selectedTagIds.length }} selected</span>
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
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Tag</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Sort
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Usage
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in taxonomy.tags" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <input
                type="checkbox"
                :checked="taxonomy.selectedTagIds.includes(item.uuid)"
                @change="taxonomy.toggleTagSelection(item.uuid)"
              />
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.slug }}</p>
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
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ item.sort_order }}</td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.contents_count ?? 0 }}
            </td>
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
        :meta="taxonomy.tagMeta"
        :loading="taxonomy.loading"
        @change="(page) => taxonomy.fetchTags({ page })"
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
        :aria-label="editing ? 'Edit tag' : 'Create tag'"
      >
        <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white px-6 py-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">
              {{ editing ? 'Edit tag' : 'Create tag' }}
            </h3>
            <p class="mt-0.5 text-sm text-slate-500">
              {{
                editing
                  ? 'Update tag metadata and SEO fields.'
                  : 'Add a CMS tag with optional SEO fields.'
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
          <TagForm
            :initial="editing || {}"
            :loading="taxonomy.saving"
            :errors="taxonomy.fieldErrors"
            :error="taxonomy.error || ''"
            :submit-label="editing ? 'Save changes' : 'Create tag'"
            @submit="saveTag"
            @cancel="closeForm"
          />
        </div>
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete tag"
      :message="`Soft delete ${pendingDelete?.name || 'this tag'}?`"
      confirm-label="Delete"
      :loading="taxonomy.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import TagForm from '@/modules/content/components/TagForm.vue';
import { useTaxonomyStore } from '@/modules/content/stores/taxonomy';

const taxonomy = useTaxonomyStore();
const showForm = ref(false);
const editing = ref(null);
const pendingDelete = ref(null);
const filters = reactive({ search: '', status: '' });

const allSelected = computed(() => {
  const ids = taxonomy.tags.map((item) => item.uuid);
  return ids.length > 0 && ids.every((id) => taxonomy.selectedTagIds.includes(id));
});

onMounted(() => {
  taxonomy.fetchTags();
});

function onFilter() {
  taxonomy.fetchTags({ ...filters, page: 1 });
}

function onReset() {
  filters.search = '';
  filters.status = '';
  taxonomy.fetchTags({ search: '', status: '', page: 1 });
}

function toggleAll() {
  taxonomy.toggleSelectAllTags(taxonomy.tags.map((item) => item.uuid));
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

async function saveTag(payload) {
  if (editing.value) {
    await taxonomy.updateTag(editing.value.uuid, payload);
  } else {
    await taxonomy.createTag(payload);
  }
  closeForm();
  await taxonomy.fetchTags();
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  await taxonomy.deleteTag(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await taxonomy.fetchTags();
}

async function runBulk(action) {
  await taxonomy.bulkTags(action);
  await taxonomy.fetchTags();
}
</script>
