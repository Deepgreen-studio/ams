<template>
  <div class="rounded-xl border border-slate-200 bg-white p-5">
    <h3 class="text-sm font-semibold text-slate-900">Identity verification</h3>
    <p class="mt-1 text-xs text-slate-500">
      Confirm the requester’s identity before approving the privacy request.
    </p>

    <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Current status</dt>
        <dd class="mt-1 text-slate-900">
          {{ request?.identity_verification_status_label || request?.identity_verification_status || '—' }}
        </dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Verified at</dt>
        <dd class="mt-1 text-slate-900">{{ formatDate(request?.identity_verified_at) }}</dd>
      </div>
    </dl>

    <div class="mt-4 space-y-3">
      <textarea
        v-model="notes"
        rows="3"
        class="input"
        placeholder="Verification notes (document checks, call reference, etc.)"
      />
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
          :disabled="loading"
          @click="$emit('verify', { verified: true, notes })"
        >
          Mark verified
        </button>
        <button
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
          :disabled="loading"
          @click="$emit('verify', { verified: false, notes })"
        >
          Mark failed
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  request: { type: Object, default: null },
  loading: { type: Boolean, default: false },
});

defineEmits(['verify']);

const notes = ref('');

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
