<template>
  <div>
    <PageHeader title="File manager" description="Nested folders and media organization." />
    <SettingsTabs>
      <div
        v-if="mediaStore.error"
        class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ mediaStore.error }}
      </div>

      <div class="mb-4 flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="createFolder"
        >
          New folder
        </button>
        <SearchBar v-model="search" class="min-w-[240px] flex-1" @search="load" />
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <EmptyState
          v-if="!mediaStore.folders.length"
          title="No folders yet"
          description="Create folders to organize uploads."
        />
        <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Name</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Slug</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Files</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="folder in mediaStore.folders"
              :key="folder.uuid"
              class="hover:bg-slate-50/80"
            >
              <td class="px-4 py-3 font-medium text-slate-900">{{ folder.name }}</td>
              <td class="px-4 py-3 text-slate-600">{{ folder.slug }}</td>
              <td class="px-4 py-3 text-slate-600">{{ folder.media_count ?? 0 }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="openDelete(folder)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </SettingsTabs>

    <DeleteConfirmation
      :open="Boolean(pending)"
      title="Delete folder"
      :message="`Delete folder ${pending?.name || ''}? Folder must be empty.`"
      :loading="mediaStore.saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import SearchBar from '@/modules/settings/components/SearchBar.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useMediaStore } from '@/modules/settings/stores/settings';

const mediaStore = useMediaStore();
const search = ref('');
const pending = ref(null);

onMounted(load);

async function load() {
  await mediaStore.fetchFolders({ search: search.value });
}

async function createFolder() {
  const name = window.prompt('Folder name');
  if (!name) return;
  await mediaStore.createFolder({ name });
  await load();
}

function openDelete(folder) {
  pending.value = folder;
}

async function confirmDelete() {
  await mediaStore.deleteFolder(pending.value.uuid);
  pending.value = null;
  await load();
}
</script>
