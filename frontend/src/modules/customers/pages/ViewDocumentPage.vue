<template>
  <div>
    <!-- <PageHeader
      :title="document?.name || 'Document details'"
      description="Preview, download, and version history."
    >
      <template #actions>
        <template v-if="document">
          <RouterLink
            :to="{ name: 'customers.documents', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="
              store.downloadDocument(document.uuid, document.original_filename || document.name)
            "
          >
            Download
          </button>
          <RouterLink
            v-if="!document.deleted_at"
            :to="{
              name: 'customers.documents.edit',
              params: { id: route.params.id, documentId: document.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="document.deleted_at"
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            :disabled="store.saving"
            @click="restore"
          >
            Restore
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="showArchive = true"
          >
            Archive
          </button>
        </template>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <template v-if="document">
        <RouterLink
          :to="{ name: 'customers.documents', params: { id: route.params.id } }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back
        </RouterLink>
        <button
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="store.downloadDocument(document.uuid, document.original_filename || document.name)"
        >
          Download
        </button>
        <button
          v-if="!document.deleted_at"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </button>
        <button
          v-if="document.deleted_at"
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="restore"
        >
          Restore
        </button>
        <button
          v-else
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
          @click="showDelete = true"
        >
          <TrashIcon class="h-4 w-4 text-white" />
          Delete
        </button>
      </template>
    </Teleport>

    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div v-if="store.loading && !document" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="document" class="space-y-6">
      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <div class="flex flex-wrap items-center gap-3">
            <DocumentStatusBadge :status="document.status" />
            <span class="text-xs uppercase tracking-wide text-slate-500">{{
              document.category_label || document.category
            }}</span>
            <span class="text-xs text-slate-500">v{{ document.version }}</span>
          </div>
          <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <dt class="text-xs text-slate-500">Filename</dt>
              <dd class="text-sm text-slate-900">{{ document.original_filename }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">MIME type</dt>
              <dd class="text-sm text-slate-900">{{ document.mime_type || '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Size</dt>
              <dd class="text-sm text-slate-900">{{ formatSize(document.size) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Expires</dt>
              <dd class="text-sm text-slate-900">{{ formatDate(document.expires_at) }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-xs text-slate-500">Notes</dt>
              <dd class="whitespace-pre-wrap text-sm text-slate-900">
                {{ document.notes || '—' }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Preview</h3>
          <div
            class="mt-4 min-h-[18rem] overflow-hidden rounded-lg border border-slate-100 bg-slate-50"
          >
            <iframe
              v-if="store.previewUrl && isPdf"
              :src="store.previewUrl"
              class="h-[22rem] w-full"
              title="Document preview"
            />
            <img
              v-else-if="store.previewUrl && isImage"
              :src="store.previewUrl"
              alt="Document preview"
              class="max-h-[22rem] w-full object-contain"
            />
            <div
              v-else
              class="flex h-[18rem] flex-col items-center justify-center gap-3 p-6 text-center text-sm text-slate-500"
            >
              <p>{{ previewMessage }}</p>
              <button
                v-if="document.is_previewable && !store.previewUrl"
                type="button"
                class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-medium text-white hover:bg-brand-700"
                @click="tryPreview"
              >
                Load preview
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h3 class="text-base font-semibold text-slate-900">Version history</h3>
          <label
            class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-brand-700"
          >
            <span>Upload new version</span>
            <input type="file" class="hidden" @change="onVersionUpload" />
          </label>
        </div>
        <ul v-if="store.versions.length" class="mt-4 divide-y divide-slate-100">
          <li
            v-for="version in store.versions"
            :key="version.uuid"
            class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"
          >
            <div>
              <p class="font-medium text-slate-900">
                v{{ version.version }} · {{ version.name }}
                <span v-if="version.is_current" class="ml-2 text-xs font-medium text-emerald-700"
                  >Current</span
                >
              </p>
              <p class="text-xs text-slate-500">
                {{ version.original_filename }} · {{ formatDate(version.created_at) }}
              </p>
            </div>
            <div class="flex gap-2">
              <RouterLink
                v-if="version.uuid !== document.uuid"
                :to="{
                  name: 'customers.documents.show',
                  params: { id: route.params.id, documentId: version.uuid },
                }"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
              >
                Open
              </RouterLink>
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                @click="
                  store.downloadDocument(version.uuid, version.original_filename || version.name)
                "
              >
                Download
              </button>
            </div>
          </li>
        </ul>
        <p v-else class="mt-3 text-sm text-slate-500">No versions loaded.</p>
      </div>

      <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h3 class="text-base font-semibold text-slate-900">Timeline</h3>
        <ol
          v-if="store.timeline.length"
          class="relative mt-6 space-y-5 border-l border-zinc-100 pl-6"
        >
          <li v-for="(item, index) in store.timeline" :key="index" class="relative">
            <span
              class="absolute -left-[1.55rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
            />
            <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
              <p class="text-sm font-medium text-slate-900">
                {{ item.description || item.event || 'Activity' }}
              </p>
              <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
            </div>
          </li>
        </ol>
        <p v-else class="mt-3 text-sm text-slate-500">No timeline entries yet.</p>
      </div>
    </div>

    <DocumentFormModal
      :open="formOpen"
      :loading="store.saving"
      :document="document"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete document"
      :message="`Soft delete ${document?.name || 'this document'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import DocumentFormModal from '@/modules/customers/components/DocumentFormModal.vue';
import DocumentStatusBadge from '@/modules/customers/components/DocumentStatusBadge.vue';
import { useCustomerDocumentsStore } from '@/modules/customers/stores/documents';

const route = useRoute();
const router = useRouter();
const store = useCustomerDocumentsStore();
const showDelete = ref(false);
const formOpen = ref(false);
const previewMessage = ref('Preview not loaded.');

const document = computed(() => store.currentDocument);
const isPdf = computed(
  () => (document.value?.mime_type || '').includes('pdf') || document.value?.extension === 'pdf',
);
const isImage = computed(() => (document.value?.mime_type || '').startsWith('image/'));

onMounted(async () => {
  await store.fetchDocument(route.params.documentId);
  await Promise.all([
    store.fetchVersions(route.params.documentId),
    store.fetchTimeline(route.params.documentId),
  ]);
  if (document.value?.is_previewable) {
    await tryPreview();
  } else {
    previewMessage.value = 'Preview is unavailable for this file type. Use download instead.';
  }
});

onBeforeUnmount(() => {
  store.revokePreview();
});

async function tryPreview() {
  try {
    await store.loadPreview(route.params.documentId);
  } catch {
    previewMessage.value = store.error || 'Unable to preview this document.';
  }
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatSize(bytes) {
  const size = Number(bytes || 0);
  if (size < 1024) return `${size} B`;
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function openEdit() {
  store.clearMessages();
  formOpen.value = true;
}

function closeForm() {
  if (store.saving) return;
  formOpen.value = false;
  store.clearMessages();
}

async function onSave(payload) {
  try {
    await store.updateDocument(route.params.documentId, payload);
    formOpen.value = false;
    await store.fetchTimeline(route.params.documentId);
  } catch {
    // Field errors stay in the modal via the store.
  }
}

async function onVersionUpload(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('file', file);
  const next = await store.uploadVersion(route.params.documentId, formData);
  event.target.value = '';
  await router.replace({
    name: 'customers.documents.show',
    params: { id: route.params.id, documentId: next.uuid },
  });
  await store.fetchDocument(next.uuid);
  await store.fetchVersions(next.uuid);
  await store.fetchTimeline(next.uuid);
  if (next.is_previewable) await store.loadPreview(next.uuid);
}

async function confirmDelete() {
  await store.archiveDocument(route.params.documentId);
  showDelete.value = false;
  await router.push({ name: 'customers.documents', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreDocument(route.params.documentId);
  await store.fetchTimeline(route.params.documentId);
}
</script>
