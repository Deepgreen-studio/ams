<template>
  <div class="space-y-4">
    <form
      class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4"
      @submit.prevent="onSubmit"
    >
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Key</label
        >
        <input
          v-model="form.key"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          required
        />
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Rollout %</label
        >
        <input
          v-model.number="form.rollout"
          type="number"
          min="0"
          max="100"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Description</label
        >
        <input
          v-model="form.description"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
      </div>
      <div class="flex items-center gap-2 md:col-span-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300" />
          Enabled
        </label>
      </div>
      <div class="flex justify-end">
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="loading"
        >
          {{ loading ? 'Saving...' : 'Save flag' }}
        </button>
      </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Flag</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Enabled</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Rollout
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="flag in flags" :key="flag.key">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ flag.key }}</p>
              <p class="text-xs text-slate-500">{{ flag.description || '—' }}</p>
            </td>
            <td class="px-4 py-3">
              <span
                class="rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                :class="
                  flag.enabled
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                    : 'bg-slate-50 text-slate-600 ring-slate-500/20'
                "
              >
                {{ flag.enabled ? 'On' : 'Off' }}
              </span>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ flag.rollout ?? 100 }}%
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                :disabled="loading"
                @click="$emit('toggle', flag)"
              >
                Toggle
              </button>
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                @click="editFlag(flag)"
              >
                Edit
              </button>
            </td>
          </tr>
          <tr v-if="!flags.length">
            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No feature flags yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';

defineProps({
  flags: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['save', 'toggle']);

const form = reactive({
  key: '',
  enabled: false,
  description: '',
  rollout: 100,
});

function editFlag(flag) {
  form.key = flag.key || '';
  form.enabled = Boolean(flag.enabled);
  form.description = flag.description || '';
  form.rollout = flag.rollout ?? 100;
}

function onSubmit() {
  emit('save', {
    key: form.key,
    enabled: form.enabled,
    description: form.description || null,
    rollout: form.rollout ?? 100,
  });
  form.key = '';
  form.enabled = false;
  form.description = '';
  form.rollout = 100;
}
</script>
