<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="application.banner" class="h-36 w-full bg-slate-100">
      <img :src="application.banner" alt="" class="h-full w-full object-cover" />
    </div>
    <div class="p-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
          <div
            class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-lg font-semibold text-brand-700"
          >
            <img
              v-if="application.icon"
              :src="application.icon"
              alt=""
              class="h-full w-full object-cover"
            />
            <span v-else>{{ initials }}</span>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ application.name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ application.slug }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <StatusBadge :status="application.platform" kind="platform" />
          <StatusBadge :status="application.status" />
          <StatusBadge :status="application.visibility" kind="visibility" />
        </div>
      </div>

      <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ application.company?.company_name || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Integration</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ application.integration?.name || 'None' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Category</dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ application.category_label || application.category || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Current version
          </dt>
          <dd class="mt-1 text-sm text-slate-900">{{ application.current_version || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Minimum supported
          </dt>
          <dd class="mt-1 text-sm text-slate-900">
            {{ application.minimum_supported_version || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Created by</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ application.creator?.full_name || '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ application.description || '—' }}</dd>
        </div>
        <div v-if="application.icon" class="sm:col-span-2">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Icon URL</dt>
          <dd class="mt-1 break-all text-sm text-slate-900">{{ application.icon }}</dd>
        </div>
        <div v-if="application.banner" class="sm:col-span-2">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Banner URL</dt>
          <dd class="mt-1 break-all text-sm text-slate-900">{{ application.banner }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '@/modules/applications/components/StatusBadge.vue';

const props = defineProps({
  application: { type: Object, required: true },
});

const initials = computed(() => (props.application?.name || 'A').slice(0, 2).toUpperCase());
</script>
