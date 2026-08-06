<template>
  <div v-if="open" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" @click.self="$emit('close')">
    <div class="max-h-[85vh] w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Version history</h3>
          <p class="text-xs text-slate-500">{{ item?.original_name }}</p>
        </div>
        <button type="button" class="rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100" @click="$emit('close')">Close</button>
      </div>
      <div class="max-h-[70vh] overflow-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">File</th>
              <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Changed</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="version in versions" :key="version.uuid">
              <td class="px-4 py-3 font-medium text-slate-900">
                v{{ version.version }}
                <span v-if="version.is_current" class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-700">Current</span>
              </td>
              <td class="px-4 py-3 text-slate-600">{{ version.original_name }} · {{ version.human_size }}</td>
              <td class="hidden px-4 py-3 text-slate-500 md:table-cell">
                {{ formatDate(version.created_at) }}
                <span class="block text-xs">{{ version.uploader?.full_name || '—' }}</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="rounded px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                  :disabled="version.is_current || restoring"
                  @click="$emit('restore', version)"
                >
                  Restore
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
defineProps({
  open: { type: Boolean, default: false },
  item: { type: Object, default: null },
  versions: { type: Array, default: () => [] },
  restoring: { type: Boolean, default: false },
});

defineEmits(['close', 'restore']);

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>
