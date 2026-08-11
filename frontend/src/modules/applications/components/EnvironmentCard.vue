<template>
  <article
    class="flex h-full flex-col rounded-[12px] bg-white p-5 ring-1 transition"
    :class="
      environment.is_current
        ? 'ring-brand-600 hover:ring-brand-700'
        : 'ring-zinc-100 hover:ring-brand-200'
    "
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="truncate text-base font-semibold tracking-tight text-slate-900">
            {{ environment.name }}
          </h3>
          <span
            v-if="environment.is_current"
            class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700"
          >
            Current
          </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">
          {{ environment.type_label || environment.type }}
        </p>
      </div>
      <EnvironmentHealthBadge :status="environment.health_status" />
    </div>

    <dl class="mt-4 space-y-3">
      <div class="rounded-[12px] bg-zinc-50 px-3.5 py-3">
        <dt class="text-xs font-medium text-zinc-500">API URL</dt>
        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
          {{ environment.api_url || '—' }}
        </dd>
      </div>
      <div class="rounded-[12px] bg-zinc-50 px-3.5 py-3">
        <dt class="text-xs font-medium text-zinc-500">Web URL</dt>
        <dd class="mt-1 break-all text-sm font-medium text-slate-900">
          {{ environment.web_url || '—' }}
        </dd>
      </div>
    </dl>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <EnvironmentHealthBadge :status="environment.status" kind="status" />
      <span
        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-600"
      >
        Vars: {{ environment.variable_keys?.length || 0 }}
      </span>
    </div>

    <div class="mt-5 flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
      <RouterLink
        :to="{
          name: 'applications.environments.show',
          params: { id: applicationId, environmentId: environment.uuid },
        }"
        class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50"
      >
        Details
      </RouterLink>
      <button
        v-if="!environment.is_current"
        type="button"
        class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-zinc-100 disabled:opacity-50"
        :disabled="switching"
        @click="$emit('switch', environment)"
      >
        Switch
      </button>
      <button
        type="button"
        class="rounded-[10px] px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-zinc-100 disabled:opacity-50"
        :disabled="checking"
        @click="$emit('health-check', environment)"
      >
        Health check
      </button>
    </div>
  </article>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import EnvironmentHealthBadge from '@/modules/applications/components/EnvironmentHealthBadge.vue';

defineProps({
  applicationId: { type: String, required: true },
  environment: { type: Object, required: true },
  switching: { type: Boolean, default: false },
  checking: { type: Boolean, default: false },
});

defineEmits(['switch', 'health-check']);
</script>
