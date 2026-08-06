<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!users.length"
      title="No users found"
      description="Try adjusting your search or create a new user."
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left">
              <input
                type="checkbox"
                class="rounded border-slate-300"
                :checked="allSelected"
                @change="$emit('toggle-all')"
              />
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">
              <button
                type="button"
                class="inline-flex items-center gap-1"
                @click="$emit('sort', 'full_name')"
              >
                Name
                <span v-if="sortBy === 'full_name'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">
              <button
                type="button"
                class="inline-flex items-center gap-1"
                @click="$emit('sort', 'email')"
              >
                Email
                <span v-if="sortBy === 'email'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">
              Phone
            </th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">
              <button
                type="button"
                class="inline-flex items-center gap-1"
                @click="$emit('sort', 'status')"
              >
                Status
                <span v-if="sortBy === 'status'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
            </th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">
              <button
                type="button"
                class="inline-flex items-center gap-1"
                @click="$emit('sort', 'created_at')"
              >
                Created
                <span v-if="sortBy === 'created_at'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
              </button>
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="user in users" :key="user.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <input
                type="checkbox"
                class="rounded border-slate-300"
                :checked="selectedIds.includes(user.uuid)"
                @change="$emit('toggle', user.uuid)"
              />
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-brand-50 text-xs font-semibold text-brand-700"
                >
                  <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    alt=""
                    class="h-full w-full object-cover"
                  />
                  <span v-else>{{ initials(user) }}</span>
                </div>
                <div>
                  <p class="font-medium text-slate-900">{{ user.full_name }}</p>
                  <p class="text-xs text-slate-500">{{ user.uuid }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ user.email }}</td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ user.phone || '—' }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="user.status" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ formatDate(user.created_at) }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'users.show', params: { id: user.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
                <RouterLink
                  :to="{ name: 'users.edit', params: { id: user.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                >
                  Edit
                </RouterLink>
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                  @click="$emit('delete', user)"
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
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import { formatDate } from '@/utils/formatters';
import StatusBadge from '@/modules/users/components/StatusBadge.vue';

const props = defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  selectedIds: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  sortBy: {
    type: String,
    default: 'created_at',
  },
  sortDir: {
    type: String,
    default: 'desc',
  },
});

defineEmits(['toggle', 'toggle-all', 'sort', 'delete']);

const allSelected = computed(() => {
  if (!props.users.length) {
    return false;
  }

  return props.users.every((user) => props.selectedIds.includes(user.uuid));
});

function initials(user) {
  const first = user.first_name?.[0] || '';
  const last = user.last_name?.[0] || '';
  return `${first}${last}`.toUpperCase() || 'U';
}
</script>
