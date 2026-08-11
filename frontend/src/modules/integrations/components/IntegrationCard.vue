<template>
  <div class="grid gap-6 xl:grid-cols-12">
    <aside class="xl:col-span-4">
      <div class="rounded-[12px] bg-white p-6 sm:p-7">
        <div class="flex flex-col items-start gap-4">
          <div
            class="inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-lg font-semibold text-brand-700"
          >
            {{ initials }}
          </div>

          <div class="min-w-0 w-full">
            <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
              {{ integration.name }}
            </h2>
            <p class="mt-1 truncate text-sm text-slate-500">{{ integration.slug }}</p>
            <div class="mt-3 flex flex-wrap gap-1.5">
              <StatusBadge :status="integration.status" />
              <StatusBadge :status="integration.health_status" kind="health" />
            </div>
          </div>
        </div>

        <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5">
          <div
            v-for="item in sidebarItems"
            :key="item.label"
            class="flex items-start justify-between gap-3"
          >
            <dt class="text-sm text-zinc-500">{{ item.label }}</dt>
            <dd class="text-right text-sm font-medium text-slate-900">{{ item.value }}</dd>
          </div>
        </dl>
      </div>
    </aside>

    <section class="space-y-6 xl:col-span-8">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="card in metricCards"
          :key="card.label"
          class="rounded-[12px] bg-white px-4 py-4 ring-1 ring-zinc-100"
        >
          <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
          <p class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-900">
            {{ card.value }}
          </p>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900">Connection</h3>
        <p class="mt-1 text-sm text-slate-500">
          Endpoint, authentication, and runtime settings for this integration.
        </p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div
            v-for="item in connectionItems"
            :key="item.label"
            class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
          >
            <p class="text-xs font-medium text-zinc-500">{{ item.label }}</p>
            <p class="mt-1 break-all text-sm font-semibold text-slate-900">{{ item.value }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-[12px] bg-white p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900">Health & credentials</h3>
        <p class="mt-1 text-sm text-slate-500">Operational status and credential configuration.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <div
            v-for="item in healthItems"
            :key="item.label"
            class="rounded-[12px] bg-zinc-50 px-4 py-3.5"
          >
            <p class="text-xs font-medium text-zinc-500">{{ item.label }}</p>
            <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ item.value }}</p>
          </div>
        </div>

        <div class="mt-4 rounded-[12px] bg-zinc-50 px-4 py-3.5">
          <p class="text-xs font-medium text-zinc-500">Description</p>
          <p class="mt-1 whitespace-pre-wrap text-sm font-medium text-slate-900">
            {{ integration.description || '—' }}
          </p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';
import StatusBadge from '@/modules/integrations/components/StatusBadge.vue';

const props = defineProps({
  integration: { type: Object, required: true },
});

const initials = computed(() =>
  String(props.integration?.name || 'I')
    .trim()
    .slice(0, 2)
    .toUpperCase(),
);

const sidebarItems = computed(() => [
  { label: 'Company', value: props.integration?.company?.company_name || '—' },
  { label: 'Created', value: formatDate(props.integration?.created_at) || '—' },
  { label: 'Updated', value: formatDate(props.integration?.updated_at) || '—' },
  {
    label: 'Created by',
    value: props.integration?.creator?.full_name || props.integration?.creator?.name || '—',
  },
]);

const metricCards = computed(() => [
  {
    label: 'Type',
    value: props.integration?.type_label || props.integration?.type || '—',
  },
  {
    label: 'Auth',
    value:
      props.integration?.authentication_type_label ||
      props.integration?.authentication_type ||
      '—',
  },
  {
    label: 'API version',
    value: props.integration?.api_version || '—',
  },
  {
    label: 'Timeout',
    value:
      props.integration?.timeout != null ? `${props.integration.timeout}s` : '—',
  },
]);

const connectionItems = computed(() => [
  {
    label: 'Base URL',
    value: props.integration?.base_url || '—',
  },
  {
    label: 'Type',
    value: props.integration?.type_label || props.integration?.type || '—',
  },
  {
    label: 'Authentication',
    value:
      props.integration?.authentication_type_label ||
      props.integration?.authentication_type ||
      '—',
  },
  {
    label: 'API version',
    value: props.integration?.api_version || '—',
  },
  {
    label: 'Timeout / retries',
    value: `${props.integration?.timeout ?? '—'}s · ${props.integration?.retry_attempts ?? '—'} retries`,
  },
  {
    label: 'Rate limit',
    value: props.integration?.rate_limit_per_minute
      ? `${props.integration.rate_limit_per_minute}/min`
      : 'Unlimited',
  },
]);

const healthItems = computed(() => [
  {
    label: 'Credentials',
    value: props.integration?.has_credentials ? 'Configured (encrypted)' : 'Not configured',
  },
  {
    label: 'Last health check',
    value: props.integration?.last_health_check
      ? formatDate(props.integration.last_health_check) || props.integration.last_health_check
      : 'Never',
  },
  {
    label: 'Health status',
    value: String(props.integration?.health_status || 'unknown')
      .replaceAll('_', ' ')
      .replace(/\b\w/g, (c) => c.toUpperCase()),
  },
  {
    label: 'Status',
    value: String(props.integration?.status || 'draft')
      .replaceAll('_', ' ')
      .replace(/\b\w/g, (c) => c.toUpperCase()),
  },
]);
</script>
