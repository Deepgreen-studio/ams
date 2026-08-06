<template>
  <article
    class="flex h-full flex-col rounded-xl border bg-white p-5 transition"
    :class="cardClass"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="text-lg font-semibold text-slate-900">{{ environment.name }}</h3>
          <span
            v-if="environment.is_current"
            class="rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-600/20"
            >Current</span
          >
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ environment.type_label || environment.type }}</p>
      </div>
      <EnvironmentHealthBadge :status="environment.health_status" />
    </div>

    <dl class="mt-4 space-y-2 text-sm">
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">API URL</dt>
        <dd class="mt-0.5 break-all text-slate-800">{{ environment.api_url || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Web URL</dt>
        <dd class="mt-0.5 break-all text-slate-800">{{ environment.web_url || '—' }}</dd>
      </div>
      <div class="flex gap-2 pt-1">
        <EnvironmentHealthBadge :status="environment.status" kind="status" />
        <span class="text-xs text-slate-500 self-center"
          >Vars: {{ environment.variable_keys?.length || 0 }}</span
        >
      </div>
    </dl>

    <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
      <RouterLink
        :to="{
          name: 'applications.environments.show',
          params: { id: applicationId, environmentId: environment.uuid },
        }"
        class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
      >
        Details
      </RouterLink>
      <button
        v-if="!environment.is_current"
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50"
        :disabled="switching"
        @click="$emit('switch', environment)"
      >
        Switch
      </button>
      <button
        type="button"
        class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50"
        :disabled="checking"
        @click="$emit('health-check', environment)"
      >
        Health check
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import EnvironmentHealthBadge from '@/modules/applications/components/EnvironmentHealthBadge.vue';

const props = defineProps({
  applicationId: { type: String, required: true },
  environment: { type: Object, required: true },
  switching: { type: Boolean, default: false },
  checking: { type: Boolean, default: false },
});

defineEmits(['switch', 'health-check']);

const cardClass = computed(() =>
  props.environment.is_current
    ? 'border-brand-300 ring-1 ring-brand-200'
    : 'border-slate-200 hover:border-brand-200',
);
</script>
