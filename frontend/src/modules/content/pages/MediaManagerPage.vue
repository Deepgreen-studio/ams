<template>
  <div>
    <ContentSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
      <MediaFolderTree
        :folders="store.folderTree"
        :selected="selectedFolder"
        @select="onSelectFolder"
        @create="openFolderModal"
      />

      <div class="space-y-4 lg:col-span-3">
        <div
          class="flex flex-col gap-3 rounded-[12px] bg-white px-5 py-5 ring-1 ring-zinc-100 sm:px-6 lg:flex-row lg:items-center lg:justify-between"
        >
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="search"
              type="search"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 shadow-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="Search by name, caption, extension…"
              @keyup.enter="reload()"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="typeFilter"
              wrapper-class="min-w-[10rem]"
              :options="typeOptions"
              @change="reload()"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="reload()"
            >
              Apply
            </button>
          </div>
        </div>

        <MediaUploadDropzone
          :progress="store.uploadProgress"
          @files="onUpload"
          @crop="cropOpen = true"
        />

        <MediaGrid
          :items="store.items"
          :loading="store.loading"
          @preview="preview = $event"
          @download="download"
          @replace="openReplace"
          @versions="openVersions"
          @delete="openDelete"
        />

        <div
          v-if="store.meta"
          class="rounded-[12px] bg-white px-6 py-4 ring-1 ring-zinc-100"
        >
          <Pagination
            :meta="store.meta"
            :loading="store.loading"
            @change="(page) => reload(page)"
          />
        </div>
      </div>
    </div>

    <CreateMediaFolderModal
      :open="folderModalOpen"
      :loading="store.saving"
      :error="folderModalError"
      :parent-label="selectedFolderLabel"
      @cancel="closeFolderModal"
      @submit="submitCreateFolder"
    />
    <MediaPreviewModal :open="Boolean(preview)" :item="preview" @close="preview = null" />
    <ImageCropModal :open="cropOpen" @close="cropOpen = false" @cropped="onCropped" />
    <MediaVersionsModal
      :open="Boolean(versionsItem)"
      :item="versionsItem"
      :versions="store.versions"
      :restoring="store.saving"
      @close="versionsItem = null"
      @restore="restoreVersion"
    />
    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete media"
      :message="`Soft delete ${pendingDelete?.original_name || 'this file'}?`"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
    <input ref="replaceInput" type="file" class="hidden" @change="onReplaceSelected" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import CreateMediaFolderModal from '@/modules/content/components/media/CreateMediaFolderModal.vue';
import ImageCropModal from '@/modules/content/components/media/ImageCropModal.vue';
import MediaFolderTree from '@/modules/content/components/media/MediaFolderTree.vue';
import MediaGrid from '@/modules/content/components/media/MediaGrid.vue';
import MediaPreviewModal from '@/modules/content/components/media/MediaPreviewModal.vue';
import MediaUploadDropzone from '@/modules/content/components/media/MediaUploadDropzone.vue';
import MediaVersionsModal from '@/modules/content/components/media/MediaVersionsModal.vue';
import { useMediaLibraryStore } from '@/modules/content/stores/mediaLibrary';
import api from '@/services/api';

const store = useMediaLibraryStore();
const search = ref('');
const typeFilter = ref('');
const selectedFolder = ref(null);
const preview = ref(null);
const pendingDelete = ref(null);
const versionsItem = ref(null);
const replaceTarget = ref(null);
const replaceInput = ref(null);
const cropOpen = ref(false);
const folderModalOpen = ref(false);
const folderModalError = ref(null);

const typeOptions = [
  { value: '', label: 'Type: All' },
  { value: 'image', label: 'Images' },
  { value: 'video', label: 'Videos' },
  { value: 'document', label: 'Documents' },
  { value: 'archive', label: 'Archives' },
];

const selectedFolderLabel = computed(() => {
  if (!selectedFolder.value) return null;
  const stack = [...(store.folderTree || [])];
  while (stack.length) {
    const node = stack.shift();
    if (node?.uuid === selectedFolder.value) return node.name || null;
    if (Array.isArray(node?.children) && node.children.length) {
      stack.push(...node.children);
    }
  }
  return null;
});

onMounted(async () => {
  await store.fetchFolderTree();
  await reload();
});

async function reload(page = 1) {
  await store.fetchMedia({
    page,
    search: search.value,
    type: typeFilter.value,
    folder: selectedFolder.value || '',
  });
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
  if (store.saving) return;
  folderModalOpen.value = false;
  folderModalError.value = null;
}

async function submitCreateFolder({ name }) {
  folderModalError.value = null;
  try {
    await store.createFolder({
      name,
      parent_id: selectedFolder.value || null,
    });
    folderModalOpen.value = false;
  } catch (err) {
    folderModalError.value =
      err?.errors?.name?.[0] || err?.message || 'Unable to create folder';
  }
}

async function onUpload(fileList) {
  if (!fileList?.length) return;
  await store.uploadFiles(fileList);
}

async function onCropped(payload) {
  await store.uploadFiles([payload.file], {
    width: payload.width,
    height: payload.height,
    crop: payload.crop,
  });
}

function download(item) {
  const url = `${api.defaults.baseURL}/content/media-library/${item.uuid}/download`;
  window.open(url, '_blank', 'noopener');
}

function openReplace(item) {
  replaceTarget.value = item;
  replaceInput.value?.click();
}

async function onReplaceSelected(event) {
  const file = event.target.files?.[0];
  const target = replaceTarget.value;
  event.target.value = '';
  if (!file || !target) return;
  await store.replaceMedia(target.uuid, file);
  replaceTarget.value = null;
}

async function openVersions(item) {
  versionsItem.value = item;
  await store.fetchVersions(item.uuid);
}

async function restoreVersion(version) {
  if (!versionsItem.value) return;
  await store.restoreVersion(versionsItem.value.uuid, version.uuid);
}

function openDelete(item) {
  pendingDelete.value = item;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;
  await store.deleteMedia(pendingDelete.value.uuid);
  pendingDelete.value = null;
}
</script>
