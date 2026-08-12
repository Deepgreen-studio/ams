<template>
  <div>
    <SettingsTabs>
      <div
        v-if="mediaStore.successMessage"
        class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
      >
        {{ mediaStore.successMessage }}
      </div>
      <div
        v-if="mediaStore.error"
        class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ mediaStore.error }}
      </div>

      <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
        >
          <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ card.label }}
            </p>
            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
              {{ card.value }}
            </p>
          </div>
          <div
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
            :class="card.iconBg"
          >
            <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
          </div>
        </div>
      </div>

      <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
        <div
          class="flex flex-col gap-4 border-b border-zinc-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
          <div class="min-w-0">
            <h2 class="text-base font-semibold text-slate-900">Folders</h2>
            <p class="mt-1 text-sm text-slate-500">
              Organize media library uploads into folders.
            </p>
          </div>
          <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
            <SearchBar v-model="search" class="min-w-0 sm:w-72" @search="load" />
            <button
              type="button"
              class="h-10 shrink-0 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="openFolderModal"
            >
              New folder
            </button>
          </div>
        </div>

        <div v-if="mediaStore.loading" class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
          <div
            v-for="n in 6"
            :key="`skeleton-${n}`"
            class="h-44 animate-pulse rounded-[12px] bg-zinc-100"
          />
        </div>

        <EmptyState
          v-else-if="!mediaStore.folders.length"
          title="No folders yet"
          description="Create folders to organize uploads across the media library."
        >
          <template #action>
            <button
              type="button"
              class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
              @click="openFolderModal"
            >
              New folder
            </button>
          </template>
        </EmptyState>

        <div v-else class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
          <FolderCard
            v-for="folder in mediaStore.folders"
            :key="folder.uuid"
            :folder="folder"
            @delete="openDelete"
          />
        </div>
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
      confirm-label="Delete"
      :loading="mediaStore.saving"
      @cancel="pending = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
  DocumentIcon,
  FolderIcon,
  FolderOpenIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CreateFolderModal from '@/modules/settings/components/CreateFolderModal.vue';
import FolderCard from '@/modules/settings/components/FolderCard.vue';
import SearchBar from '@/modules/settings/components/SearchBar.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useMediaStore } from '@/modules/settings/stores/settings';

const mediaStore = useMediaStore();
const search = ref('');
const pending = ref(null);
const folderModalOpen = ref(false);
const folderModalError = ref(null);

const statCards = computed(() => {
  const folders = mediaStore.folders || [];
  const totalFiles = folders.reduce((sum, folder) => sum + Number(folder.media_count ?? 0), 0);
  const emptyFolders = folders.filter((folder) => Number(folder.media_count ?? 0) === 0).length;

  return [
    {
      label: 'Folders',
      value: folders.length,
      icon: FolderIcon,
      iconBg: 'bg-brand-50',
      iconColor: 'text-brand-500',
    },
    {
      label: 'Files',
      value: totalFiles,
      icon: DocumentIcon,
      iconBg: 'bg-sky-50',
      iconColor: 'text-sky-600',
    },
    {
      label: 'Empty',
      value: emptyFolders,
      icon: FolderOpenIcon,
      iconBg: 'bg-amber-50',
      iconColor: 'text-amber-600',
    },
  ];
});

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
