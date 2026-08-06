<template>
  <div class="space-y-4">
    <form class="grid gap-3 sm:grid-cols-4" @submit.prevent="onShare">
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Share type</label>
        <select v-model="form.share_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="role">Role</option>
          <option value="user">User</option>
          <option value="company">Company</option>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          {{ form.share_type === 'role' ? 'Role name / UUID' : 'Target UUID / ID' }}
        </label>
        <input
          v-model="form.identifier"
          required
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
          :placeholder="form.share_type === 'role' ? 'manager' : 'uuid or numeric id'"
        />
      </div>
      <div class="flex items-end gap-3">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.can_edit" type="checkbox" class="rounded border-slate-300" />
          Can edit
        </label>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="saving"
        >
          Share
        </button>
      </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Type</th>
            <th class="px-3 py-2 font-medium">Target</th>
            <th class="px-3 py-2 font-medium">Access</th>
            <th class="px-3 py-2 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="share in shares" :key="share.uuid" class="border-b border-slate-100">
            <td class="px-3 py-2 capitalize">{{ share.share_type }}</td>
            <td class="px-3 py-2">{{ targetLabel(share) }}</td>
            <td class="px-3 py-2">{{ share.can_edit ? 'Edit' : 'View' }}</td>
            <td class="px-3 py-2">
              <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="emit('revoke', share)">
                Revoke
              </button>
            </td>
          </tr>
          <tr v-if="!shares.length">
            <td colspan="4" class="px-3 py-6 text-center text-slate-500">Not shared yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';

defineProps({
  shares: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['share', 'revoke']);

const form = reactive({
  share_type: 'role',
  identifier: '',
  can_edit: false,
});

function targetLabel(share) {
  const target = share.target || {};
  return target.display_name || target.full_name || target.company_name || target.name || `#${share.share_id}`;
}

function onShare() {
  const payload = {
    share_type: form.share_type,
    can_edit: form.can_edit,
  };

  if (/^\d+$/.test(form.identifier.trim())) {
    payload.share_id = Number(form.identifier.trim());
  } else {
    payload.identifier = form.identifier.trim();
  }

  emit('share', payload);
  form.identifier = '';
  form.can_edit = false;
}
</script>
