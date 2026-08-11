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

      <div class="grid gap-6 lg:grid-cols-4">
        <FolderTree
          :folders="mediaStore.folders"
          :selected="selectedFolder"
          @select="onSelectFolder"
          @create="openFolderModal"
        />
        <div class="space-y-4 lg:col-span-3">
          <div
            class="rounded-[12px] bg-white px-5 py-5 ring-1 ring-zinc-100 sm:px-6"
          >
            <SearchBar v-model="search" @search="reload" />
          </div>
          <MediaUpload :progress="progress" @files="onUpload" />
          <MediaGrid
            :items="mediaStore.items"
            :loading="mediaStore.loading"
            @preview="preview = $event"
            @delete="openDelete"
          />
          <div
            v-if="mediaStore.meta"
            class="rounded-[12px] bg-white px-6 py-4 ring-1 ring-zinc-100"
          >
            <Pagination
              :meta="mediaStore.meta"
              :loading="mediaStore.loading"
              @change="(page) => reload(page)"
            />
          </div>
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
    <PreviewModal :open="Boolean(preview)" :item="preview" @close="preview = null" />
    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete media"
      :message="`Delete ${pendingDelete?.original_name || 'this file'}?`"
      :loading="mediaStore.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import CreateFolderModal from '@/modules/settings/components/CreateFolderModal.vue';
import FolderTree from '@/modules/settings/components/FolderTree.vue';
import MediaGrid from '@/modules/settings/components/MediaGrid.vue';
import MediaUpload from '@/modules/settings/components/MediaUpload.vue';
import PreviewModal from '@/modules/settings/components/PreviewModal.vue';
import SearchBar from '@/modules/settings/components/SearchBar.vue';
import SettingsTabs from '@/modules/settings/components/SettingsTabs.vue';
import { useMediaStore } from '@/modules/settings/stores/settings';

const mediaStore = useMediaStore();
const search = ref('');
const selectedFolder = ref(null);
const preview = ref(null);
const pendingDelete = ref(null);
const progress = ref(0);
const folderModalOpen = ref(false);
const folderModalError = ref(null);

onMounted(async () => {
  await mediaStore.fetchFolders();
  await reload();
});

async function reload(page = 1) {
  const params = { page, per_page: 12, search: search.value };
  if (selectedFolder.value) params.folder = selectedFolder.value;
  await mediaStore.fetchMedia(params);
}

function onSelectFolder(folder) {
  selectedFolder.value = folder?.uuid || null;
  reload();
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
    await mediaStore.fetchFolders();
  } catch (err) {
    folderModalError.value =
      err?.errors?.name?.[0] || err?.message || 'Unable to create folder';
  }
}

async function onUpload(fileList) {
  if (!fileList?.length) return;
  progress.value = 40;
  await mediaStore.upload(fileList, selectedFolder.value);
  progress.value = 100;
  setTimeout(() => {
    progress.value = 0;
  }, 400);
  await reload();
  await mediaStore.fetchFolders();
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  await mediaStore.remove(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await reload();
}
</script>
