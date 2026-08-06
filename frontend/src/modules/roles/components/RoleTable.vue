<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!roles.length"
      title="No roles found"
      description="Adjust your filters or create a new role."
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Role</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Machine name
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Permissions</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              Users
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="role in roles" :key="role.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center gap-2">
                <p class="font-medium text-slate-900">{{ role.display_name }}</p>
                <RoleBadge v-if="role.is_system" name="System" system />
              </div>
              <p class="mt-1 max-w-md text-xs text-slate-500">{{ role.description || '—' }}</p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ role.name }}</td>
            <td class="px-4 py-3 text-slate-700">{{ role.permissions_count ?? 0 }}</td>
            <td class="hidden px-4 py-3 text-slate-700 lg:table-cell">
              {{ role.users_count ?? 0 }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'roles.show', params: { id: role.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'roles.edit', params: { id: role.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <RouterLink
                  :to="{ name: 'roles.permissions', params: { id: role.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Permissions
                </RouterLink>
                <button
                  v-if="!role.is_system"
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('delete', role)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import RoleBadge from '@/modules/roles/components/RoleBadge.vue';

defineProps({
  roles: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['delete']);
</script>
