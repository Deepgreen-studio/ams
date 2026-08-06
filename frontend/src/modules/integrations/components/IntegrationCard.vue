<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">{{ integration.name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ integration.slug }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <StatusBadge :status="integration.status" />
        <StatusBadge :status="integration.health_status" kind="health" />
      </div>
    </div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ integration.company?.company_name || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Type</dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{ integration.type_label || integration.type }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Authentication</dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{ integration.authentication_type_label || integration.authentication_type }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Base URL</dt>
        <dd class="mt-1 break-all text-sm text-slate-900">{{ integration.base_url || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">API version</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ integration.api_version || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
          Timeout / retries
        </dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{ integration.timeout }}s · {{ integration.retry_attempts }} retries
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Rate limit</dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{
            integration.rate_limit_per_minute
              ? `${integration.rate_limit_per_minute}/min`
              : 'Unlimited'
          }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Credentials</dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{ integration.has_credentials ? 'Configured (encrypted)' : 'Not configured' }}
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
          Last health check
        </dt>
        <dd class="mt-1 text-sm text-slate-900">{{ integration.last_health_check || 'Never' }}</dd>
      </div>
      <div class="sm:col-span-2">
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ integration.description || '—' }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import StatusBadge from '@/modules/integrations/components/StatusBadge.vue';

defineProps({
  integration: { type: Object, required: true },
});
</script>
