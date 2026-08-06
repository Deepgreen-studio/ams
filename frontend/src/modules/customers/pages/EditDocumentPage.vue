<template>
  <div>
    <PageHeader
      title="Edit document"
      description="Update document metadata (name, folder, status, expiry)."
    />
    <div v-if="store.loading && !document" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    <form
      v-else-if="document"
      class="space-y-4 rounded-xl border border-slate-200 bg-white p-6"
      @submit.prevent="onSubmit"
    >
      <div
        v-if="store.error"
        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ store.error }}
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
          <input v-model="form.name" type="text" class="input" required />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Category / folder</label>
          <select v-model="form.category" class="input" required>
            <option value="contracts">Contracts</option>
            <option value="nda">NDA</option>
            <option value="invoices">Invoices</option>
            <option value="certificates">Certificates</option>
            <option value="attachments">Attachments</option>
            <option value="custom">Custom Documents</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
          <select v-model="form.status" class="input" required>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Expiry date</label>
          <input v-model="form.expires_at" type="datetime-local" class="input" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
          <textarea v-model="form.notes" rows="3" class="input" />
        </div>
      </div>
      <div class="flex justify-end gap-3">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="
            router.push({
              name: 'customers.documents.show',
              params: { id: route.params.id, documentId: route.params.documentId },
            })
          "
        >
          Cancel
        </button>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving...' : 'Save changes' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useCustomerDocumentsStore } from '@/modules/customers/stores/documents';

const route = useRoute();
const router = useRouter();
const store = useCustomerDocumentsStore();

const document = computed(() => store.currentDocument);

const form = reactive({
  name: '',
  category: 'contracts',
  status: 'active',
  expires_at: '',
  notes: '',
});

watch(
  document,
  (value) => {
    if (!value) return;
    form.name = value.name || '';
    form.category = value.category || 'contracts';
    form.status = value.status || 'active';
    form.expires_at = toLocalInput(value.expires_at);
    form.notes = value.notes || '';
  },
  { immediate: true },
);

onMounted(() => {
  store.fetchDocument(route.params.documentId);
});

function toLocalInput(value) {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

async function onSubmit() {
  const payload = {
    name: form.name,
    category: form.category,
    status: form.status,
    notes: form.notes || null,
    expires_at: form.expires_at ? new Date(form.expires_at).toISOString() : null,
  };
  await store.updateDocument(route.params.documentId, payload);
  await router.push({
    name: 'customers.documents.show',
    params: { id: route.params.id, documentId: route.params.documentId },
  });
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>
