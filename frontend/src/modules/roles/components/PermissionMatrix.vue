<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 6" :key="n" class="h-12 animate-pulse rounded bg-slate-100" />
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Module / Permission</th>
            <th class="px-4 py-3 text-center font-semibold text-slate-600">Assigned</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="group in matrix" :key="group.uuid || group.id">
            <tr class="bg-slate-50/80">
              <td
                colspan="2"
                class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                {{ group.name }}
              </td>
            </tr>
            <tr
              v-for="permission in group.permissions || []"
              :key="permission.id || permission.name"
              class="border-t border-slate-100"
            >
              <td class="px-4 py-2">
                <p class="font-medium text-slate-800">
                  {{ permission.display_name || permission.name }}
                </p>
                <p class="text-xs text-slate-500">{{ permission.name }}</p>
              </td>
              <td class="px-4 py-2 text-center">
                <span
                  class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="
                    permission.assigned
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-slate-100 text-slate-500'
                  "
                >
                  {{ permission.assigned ? 'Yes' : 'No' }}
                </span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  matrix: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
</script>
