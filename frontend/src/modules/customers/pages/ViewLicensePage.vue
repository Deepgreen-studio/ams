<template>
  <div>
    <!-- <PageHeader
      :title="license?.license_key || 'License details'"
      description="License key, activations, and status."
    >
      <template #actions>
        <template v-if="license">
          <RouterLink
            :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <RouterLink
            v-if="!license.deleted_at"
            :to="{
              name: 'customers.licenses.edit',
              params: { id: route.params.id, licenseId: license.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="license.status !== 'revoked' && !license.deleted_at"
            type="button"
            class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
            :disabled="store.saving"
            @click="revoke"
          >
            Revoke
          </button>
          <button
            v-if="license.deleted_at"
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
      <template v-if="license">
          <RouterLink
            :to="{ name: 'customers.licenses', params: { id: route.params.id } }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Back
          </RouterLink>
          <RouterLink
            v-if="!license.deleted_at"
            :to="{
              name: 'customers.licenses.edit',
              params: { id: route.params.id, licenseId: license.uuid },
            }"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            Edit
          </RouterLink>
          <button
            v-if="license.status !== 'revoked' && !license.deleted_at"
            type="button"
            class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-50"
            :disabled="store.saving"
            @click="revoke"
          >
            Revoke
          </button>
          <button
            v-if="license.deleted_at"
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
    </Teleport>

    <div
      v-if="store.error"
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

    <div v-if="store.loading && !license" class="h-48 animate-pulse rounded-xl bg-slate-100" />

    <div v-else-if="license" class="space-y-6">
      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <LicenseStatusBadge :status="license.status" />
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">License key</dt>
            <dd class="font-mono text-sm text-slate-900">{{ license.license_key }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Subscription</dt>
            <dd class="text-sm text-slate-900">{{ license.subscription?.plan_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Payment status</dt>
            <dd>
              <PaymentStatusBadge :status="license.subscription?.payment_status || 'pending'" />
            </dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Activations</dt>
            <dd class="text-sm text-slate-900">
              {{ license.activation_count }}/{{ license.max_activations }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Expires</dt>
            <dd class="text-sm text-slate-900">{{ formatDate(license.expires_at) }}</dd>
          </div>
          <div v-if="license.revoked_reason" class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Revoked reason</dt>
            <dd class="text-sm text-slate-900">{{ license.revoked_reason }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs text-slate-500">Features</dt>
            <dd class="text-sm text-slate-900">{{ (license.features || []).join(',') || '—' }}</dd>
          </div>
        </dl>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Timeline</h3>
        <ul v-if="store.timeline.length" class="mt-4 space-y-3">
          <li
            v-for="(item, index) in store.timeline"
            :key="index"
            class="border-l-2 border-slate-200 pl-3 text-sm"
          >
            <p class="font-medium text-slate-900">
              {{ item.description || item.event || 'Activity' }}
            </p>
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
          </li>
        </ul>
        <p v-else class="mt-3 text-sm text-slate-500">No timeline entries yet.</p>
      </div>
    </div>

    <DeleteConfirmation
      :open="showArchive"
      title="Archive license"
      :message="`Archive ${license?.license_key || 'this license'}?`"
      confirm-label="Archive"
      :loading="store.saving"
      @cancel="showArchive = false"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import LicenseStatusBadge from '@/modules/customers/components/LicenseStatusBadge.vue';
import PaymentStatusBadge from '@/modules/customers/components/PaymentStatusBadge.vue';
import { useLicensesStore } from '@/modules/customers/stores/licenses';

const route = useRoute();
const router = useRouter();
const store = useLicensesStore();
const showArchive = ref(false);

const license = computed(() => store.currentLicense);

onMounted(async () => {
  await store.fetchLicense(route.params.licenseId);
  await store.fetchTimeline(route.params.licenseId);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function revoke() {
  const reason = window.prompt('Revocation reason (optional)') || '';
  await store.revokeLicense(route.params.licenseId, reason);
  await store.fetchTimeline(route.params.licenseId);
}

async function confirmArchive() {
  await store.archiveLicense(route.params.licenseId);
  showArchive.value = false;
  await router.push({ name: 'customers.licenses', params: { id: route.params.id } });
}

async function restore() {
  await store.restoreLicense(route.params.licenseId);
  await store.fetchTimeline(route.params.licenseId);
}
</script>
