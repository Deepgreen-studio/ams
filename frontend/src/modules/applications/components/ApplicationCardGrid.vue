<template>
  <div>
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-48 animate-pulse rounded-xl bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!applications.length"
      title="No applications found"
      description="Register a customer application to manage it from one dashboard."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in applications"
        :key="item.uuid"
        class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 transition hover:border-brand-200 hover:shadow-md"
      >
        <div class="flex items-start gap-3">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-sm font-semibold text-brand-700"
          >
            <img v-if="item.icon" :src="item.icon" alt="" class="h-full w-full object-cover" />
            <span v-else>{{ initials(item.name) }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-base font-semibold text-slate-900">{{ item.name }}</h3>
            <p class="truncate text-xs text-slate-500">{{ item.company?.company_name || '—' }}</p>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <StatusBadge :status="item.platform" kind="platform" />
          <StatusBadge :status="item.status" />
          <StatusBadge :status="item.visibility" kind="visibility" />
        </div>

        <p class="mt-3 line-clamp-2 flex-1 text-sm text-slate-600">
          {{ item.description || 'No description provided.' }}
        </p>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
          <span class="text-xs text-slate-500">v{{ item.current_version || '—' }}</span>
          <div class="flex gap-2">
            <RouterLink
              :to="{ name: 'applications.show', params: { id: item.uuid } }"
              class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
              >View</RouterLink
            >
            <RouterLink
              :to="{ name: 'applications.edit', params: { id: item.uuid } }"
              class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
              >Edit</RouterLink
            >
            <button
              type="button"
              class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
              @click="$emit('delete', item)"
            >
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';

defineProps({
  applications: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

function initials(name) {
  return (name || 'A').slice(0, 2).toUpperCase();
}
</script>
