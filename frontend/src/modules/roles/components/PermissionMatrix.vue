<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="loading" class="space-y-3 px-6 py-5">
      <div v-for="n in 6" :key="n" class="h-12 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <div v-else-if="!matrix.length" class="px-6 py-8 text-center text-sm text-slate-500">
      Select a role to view the permission matrix.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">
              Module / Permission
            </th>
            <th class="px-5 py-3 text-center text-sm font-semibold text-zinc-500">Assigned</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="group in matrix" :key="group.uuid || group.id">
            <tr class="border-b border-zinc-100 bg-zinc-50/70">
              <td
                colspan="2"
                class="px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-zinc-400"
              >
                {{ group.name }}
              </td>
            </tr>
            <tr
              v-for="permission in group.permissions || []"
              :key="permission.id || permission.name"
              class="border-b border-zinc-100 last:border-b-0 transition hover:bg-zinc-50/60"
            >
              <td class="px-5 py-3">
                <p class="font-medium text-slate-800">
                  {{ permission.display_name || permission.name }}
                </p>
              </td>
              <td class="px-5 py-3 text-center">
                <input
                  type="checkbox"
                  :checked="Boolean(permission.assigned)"
                  tabindex="-1"
                  class="pointer-events-none h-4 w-4 accent-brand-600"
                  :aria-checked="Boolean(permission.assigned)"
                  :aria-label="`${permission.display_name || permission.name} assigned`"
                />
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
