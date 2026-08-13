<template>
  <div class="space-y-4">
    <form class="grid gap-3 sm:grid-cols-4" @submit.prevent="onShare">
      <div>
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Share type</label>
        <SelectBox v-model="form.share_type" :options="shareTypeOptions" />
      </div>
      <div class="sm:col-span-2">
        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">
          {{ form.share_type === 'role' ? 'Role name / UUID' : 'Target UUID / ID' }}
        </label>
        <input
          v-model="form.identifier"
          required
          class="input"
          :placeholder="form.share_type === 'role' ? 'manager' : 'uuid or numeric id'"
        />
      </div>
      <div class="flex items-end gap-3">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.can_edit" type="checkbox" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
          Can edit
        </label>
        <button
          type="submit"
          class="inline-flex items-center rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="saving"
        >
          Share
        </button>
      </div>
    </form>

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <EmptyState
        v-if="!shares.length"
        title="Not shared yet"
        description="Share this dashboard with a role, user, or company to grant access."
      />
      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-left text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 font-semibold text-zinc-500">Type</th>
              <th class="px-5 py-3 font-semibold text-zinc-500">Target</th>
              <th class="px-5 py-3 font-semibold text-zinc-500">Access</th>
              <th class="px-5 py-3 text-right font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="share in shares"
              :key="share.uuid"
              class="border-b border-zinc-50 last:border-0 hover:bg-zinc-50/80"
            >
              <td class="px-5 py-4 capitalize text-slate-700">{{ share.share_type }}</td>
              <td class="px-5 py-4 text-slate-700">{{ targetLabel(share) }}</td>
              <td class="px-5 py-4 text-slate-700">{{ share.can_edit ? 'Edit' : 'View' }}</td>
              <td class="px-5 py-4 text-right">
                <button
                  type="button"
                  class="rounded-[12px] px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="emit('revoke', share)"
                >
                  Revoke
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

defineProps({
  shares: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['share', 'revoke']);

const shareTypeOptions = [
  { value: 'role', label: 'Role' },
  { value: 'user', label: 'User' },
  { value: 'company', label: 'Company' },
];

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
