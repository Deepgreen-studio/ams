<template>
  <div class="rounded-xl border border-slate-200 bg-white p-5">
    <h3 class="text-sm font-semibold text-slate-900">Approval workflow</h3>
    <p class="mt-1 text-xs text-slate-500">
      Approve or reject after identity verification. Export or deletion actions unlock after approval.
    </p>

    <div class="mt-4 space-y-3">
      <textarea
        v-model="notes"
        rows="3"
        class="input"
        placeholder="Decision notes"
      />
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
          :disabled="loading || !canDecide"
          @click="$emit('approve', { decision: 'approved', notes })"
        >
          Approve
        </button>
        <button
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
          :disabled="loading || !canDecide || !notes.trim()"
          @click="$emit('reject', { notes })"
        >
          Reject
        </button>
        <button
          v-if="request?.requires_export"
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading || !canFulfil"
          @click="$emit('export')"
        >
          {{ request?.has_export ? 'Regenerate export' : 'Generate export' }}
        </button>
        <button
          v-if="request?.has_export"
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="$emit('download')"
        >
          Download export
        </button>
        <button
          v-if="request?.requires_deletion"
          type="button"
          class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-60"
          :disabled="loading || !canFulfil || Boolean(request?.deletion_confirmed_at)"
          @click="$emit('delete-data', { confirmed: true, notes })"
        >
          {{ request?.deletion_confirmed_at ? 'Deletion confirmed' : 'Confirm data deletion' }}
        </button>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="loading || !canComplete"
          @click="$emit('complete', { notes })"
        >
          Complete request
        </button>
      </div>
      <p v-if="!canDecide" class="text-xs text-amber-700">
        Identity must be verified and the request must be under review before approval.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  request: { type: Object, default: null },
  loading: { type: Boolean, default: false },
});

defineEmits(['approve', 'reject', 'export', 'download', 'delete-data', 'complete']);

const notes = ref('');

const canDecide = computed(() => {
  const identity = props.request?.identity_verification_status;
  const status = props.request?.status;
  return (
    (identity === 'verified' || identity === 'not_required') && status === 'under_review'
  );
});

const canFulfil = computed(() =>
  ['approved', 'in_progress'].includes(props.request?.status)
);

const canComplete = computed(() =>
  ['approved', 'in_progress'].includes(props.request?.status)
);
</script>
