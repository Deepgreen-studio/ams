<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div v-if="$slots.toolbar" class="border-b border-zinc-100 px-8 py-6">
      <slot name="toolbar" />
    </div>

    <div v-if="loading" class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3">
      <div v-for="n in 6" :key="n" class="h-48 animate-pulse rounded-[12px] bg-slate-100" />
    </div>

    <EmptyState
      v-else-if="!applications.length"
      title="No applications found"
      description="Try adjusting your search or create a new application."
      class="px-8 py-6"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>

    <div v-else class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="item in applications"
        :key="item.uuid"
        class="flex flex-col rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="flex items-start gap-3">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-sm font-semibold text-brand-700"
          >
            <img
              v-if="iconSrc(item) && !failedIcons[item.uuid]"
              :src="iconSrc(item)"
              alt=""
              class="h-full w-full object-cover"
              @error="markIconFailed(item.uuid)"
            />
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

        <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-4">
          <span class="text-xs text-slate-500">v{{ item.current_version || '—' }}</span>
          <div class="flex gap-1">
            <RouterLink
              :to="{ name: 'applications.show', params: { id: item.uuid } }"
              class="rounded-[12px] px-2.5 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50"
            >
              View
            </RouterLink>
            <RouterLink
              :to="{ name: 'applications.edit', params: { id: item.uuid } }"
              class="rounded-[12px] px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-zinc-50"
            >
              Edit
            </RouterLink>
            <button
              type="button"
              class="rounded-[12px] px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
              @click="$emit('delete', item)"
            >
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>

    <div v-if="$slots.footer" class="border-t border-zinc-100 px-8 py-5">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';
import { resolveMediaUrl } from '@/utils/mediaUrl';

defineProps({
  applications: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(['delete']);

const failedIcons = reactive({});

function initials(name) {
  return String(name || 'A')
    .trim()
    .slice(0, 2)
    .toUpperCase();
}

function iconSrc(item) {
  return resolveMediaUrl(item?.icon || '');
}

function markIconFailed(uuid) {
  failedIcons[uuid] = true;
}
</script>
