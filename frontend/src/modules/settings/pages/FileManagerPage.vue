<template>
  <div>
    <SettingsTabs>
      <div
        v-if="mediaStore.error"
        class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ mediaStore.error }}
      </div>

      <div
        class="mb-4 flex flex-col gap-3 rounded-[12px] bg-white px-5 py-5 ring-1 ring-zinc-100 sm:flex-row sm:items-center sm:px-6"
      >
        <button
          type="button"
          class="h-10 shrink-0 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
          @click="openFolderModal"
        >
          New folder
        </button>
        <SearchBar v-model="search" class="min-w-0 flex-1" @search="load" />
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <EmptyState
          v-if="!mediaStore.folders.length"
          title="No folders yet"
          description="Create folders to organize uploads."
        />
        <table v-else class="min-w-full divide-y divide-zinc-100 text-sm">
          <thead class="bg-zinc-50/80">
            <tr>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Name
              </th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Slug
              </th>
              <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Files
              </th>
              <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100">
            <tr
              v-for="folder in mediaStore.folders"
              :key="folder.uuid"
              class="hover:bg-zinc-50/80"
            >
              <td class="px-5 py-3.5 font-medium text-slate-900">{{ folder.name }}</td>
              <td class="px-5 py-3.5 text-slate-600">{{ folder.slug }}</td>
              <td class="px-5 py-3.5 text-slate-600">{{ folder.media_count ?? 0 }}</td>
              <td class="px-5 py-3.5 text-right">
                <button
                  type="button"
                  class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
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

    <CreateFolderModal
      :open="folderModalOpen"
      :loading="mediaStore.saving"
      :error="folderModalError"
      @cancel="closeFolderModal"
      @submit="submitCreateFolder"
    />
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
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CreateFolderModal from '@/modules/settings/components/CreateFolderModal.vue';
import SearchBar from '@/modules/settings/components/SearchBar.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useMediaStore } from '@/modules/settings/stores/settings';

const mediaStore = useMediaStore();
const search = ref('');
const pending = ref(null);
const folderModalOpen = ref(false);
const folderModalError = ref(null);

onMounted(load);

async function load() {
  await mediaStore.fetchFolders({ search: search.value });
}

function openFolderModal() {
  folderModalError.value = null;
  folderModalOpen.value = true;
}

function closeFolderModal() {
  if (mediaStore.saving) return;
  folderModalOpen.value = false;
  folderModalError.value = null;
}

async function submitCreateFolder({ name }) {
  folderModalError.value = null;
  try {
    await mediaStore.createFolder({ name });
    folderModalOpen.value = false;
    await load();
  } catch (err) {
    folderModalError.value =
      err?.errors?.name?.[0] || err?.message || 'Unable to create folder';
  }
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
