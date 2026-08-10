<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <template v-if="license">
        <RouterLink
          :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back
        </RouterLink>
        <button
          v-if="!license.deleted_at"
          type="button"
          class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-4 w-4 text-slate-500" />
          Edit
        </button>
        <button
          v-if="license.status !== 'revoked' && !license.deleted_at"
          type="button"
          class="rounded-[12px] border border-amber-300 px-5 py-2.5 text-sm font-medium text-amber-800 hover:bg-amber-50"
          :disabled="store.saving"
          @click="revoke"
        >
          Revoke
        </button>
        <button
          v-if="license.deleted_at"
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
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error && !formOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div
      v-if="store.loading && !license"
      class="h-48 animate-pulse rounded-[12px] bg-slate-100"
    />

    <div v-else-if="license" class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-mono text-lg font-semibold text-slate-900">{{ license.license_key }}</p>
              <p class="mt-1 text-sm text-slate-500">
                {{ license.subscription?.plan_name || 'No linked plan' }}
              </p>
            </div>
            <LicenseStatusBadge :status="license.status" />
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
          <h3 class="text-base font-semibold text-slate-900">License details</h3>
          <dl class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
            <div
              v-for="item in detailItems"
              :key="item.label"
              class="grid grid-cols-[8.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[10rem_1fr]"
            >
              <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
              <dd class="text-sm font-medium text-slate-900 whitespace-pre-wrap">
                <PaymentStatusBadge
                  v-if="item.type === 'payment'"
                  :status="item.value"
                />
                <template v-else>{{ item.value }}</template>
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6 sm:p-8">
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

      <div class="space-y-6">
        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Usage</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Activations</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ license.activation_count }}/{{ license.max_activations }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Starts</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(license.starts_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Expires</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ license.expires_at ? formatDate(license.expires_at) : 'Lifetime' }}
              </dd>
            </div>
          </dl>
        </div>

        <div class="rounded-[12px] bg-white p-6">
          <h3 class="text-base font-semibold text-slate-900">Record</h3>
          <dl class="mt-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Created</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(license.created_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Updated</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ formatDate(license.updated_at) }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm text-zinc-500">Deleted</dt>
              <dd class="text-sm font-medium text-slate-900">
                {{ license.deleted_at ? formatDate(license.deleted_at) : '—' }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <LicenseFormModal
      :open="formOpen"
      :loading="store.saving"
      :license="license"
      :customer-id="route.params.id"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeForm"
      @submit="onSave"
    />

    <DeleteConfirmation
      :open="showDelete"
      title="Delete license"
      :message="`Soft delete ${license?.license_key || 'this license'}? It can be restored later.`"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="showDelete = false"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import LicenseFormModal from '@/modules/customers/components/LicenseFormModal.vue';
import LicenseStatusBadge from '@/modules/customers/components/LicenseStatusBadge.vue';
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const router = useRouter();
const store = useLicensesStore();
const showDelete = ref(false);
const formOpen = ref(false);

const license = computed(() => store.currentLicense);

const detailItems = computed(() => [
  { label: 'Subscription', value: license.value?.subscription?.plan_name || '—' },
  {
    label: 'Payment',
    type: 'payment',
    value: license.value?.subscription?.payment_status || 'pending',
  },
  {
    label: 'Activations',
    value: `${license.value?.activation_count ?? 0}/${license.value?.max_activations ?? 0}`,
  },
  {
    label: 'Expires',
    value: license.value?.expires_at ? formatDate(license.value.expires_at) : 'Lifetime',
  },
  ...(license.value?.revoked_reason
    ? [{ label: 'Revoked reason', value: license.value.revoked_reason }]
    : []),
  {
    label: 'Features',
    value: (license.value?.features || []).join(', ') || '—',
  },
  { label: 'Notes', value: license.value?.notes || '—' },
]);

onMounted(async () => {
  await store.fetchLicense(route.params.licenseId);
  await store.fetchTimeline(route.params.licenseId);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
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

function sanitize(payload) {
  const next = { ...payload };
  delete next.subscription_id;
  ['starts_at', 'expires_at', 'notes'].forEach((key) => {
    if (next[key] === '') next[key] = null;
  });
  if (next.starts_at) next.starts_at = new Date(next.starts_at).toISOString();
  if (next.expires_at) next.expires_at = new Date(next.expires_at).toISOString();
  next.max_activations = Number(next.max_activations || 5);
  return next;
}

async function onSave(payload) {
  try {
    await store.updateLicense(route.params.licenseId, sanitize(payload));
    formOpen.value = false;
    await store.fetchTimeline(route.params.licenseId);
  } catch {
    // Field errors stay in the modal via the store.
  }
}

async function revoke() {
  const reason = window.prompt('Revocation reason (optional)') || '';
  await store.revokeLicense(route.params.licenseId, reason);
  await store.fetchTimeline(route.params.licenseId);
}

async function confirmDelete() {
  await store.archiveLicense(route.params.licenseId);
  showDelete.value = false;
  await router.push({ name: 'customers.licenses', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreLicense(route.params.licenseId);
  await store.fetchTimeline(route.params.licenseId);
}
</script>
