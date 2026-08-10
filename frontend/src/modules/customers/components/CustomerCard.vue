<template>
  <div class="rounded-[12px] bg-white p-6 sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-[12px] bg-brand-50 text-base font-semibold text-brand-700"
      >
        {{ initials }}
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
            {{ customer?.display_name }}
          </h2>
          <TypeBadge :type="customer?.customer_type" />
          <StatusBadge :status="customer?.status || 'active'" />
        </div>
        <p class="mt-1 truncate text-sm text-slate-500">{{ customer?.email || '—' }}</p>
      </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
      <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
        <p class="text-xs text-zinc-500">Company</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">
          {{ customer?.company?.company_name || '—' }}
        </p>
      </div>
      <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
        <p class="text-xs text-zinc-500">Country</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ customer?.country || '—' }}</p>
      </div>
      <div class="rounded-[12px] bg-zinc-50 px-4 py-3">
        <p class="text-xs text-zinc-500">Industry</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ customer?.industry || '—' }}</p>
      </div>
    </div>

    <div class="mt-5">
      <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
        Contact
      </p>
      <dl class="divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
        <div
          v-for="item in contactItems"
          :key="item.label"
          class="grid grid-cols-[7.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[8.5rem_1fr]"
        >
          <dt class="text-xs font-medium text-slate-500">{{ item.label }}</dt>
          <dd class="min-w-0 truncate text-sm font-medium text-slate-900">
            <a
              v-if="item.href"
              :href="item.href"
              target="_blank"
              rel="noopener noreferrer"
              class="text-brand-600 hover:text-brand-700"
            >
              {{ item.value }}
            </a>
            <span v-else>{{ item.value }}</span>
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import TypeBadge from '@/modules/customers/components/TypeBadge.vue';

const props = defineProps({
  customer: {
    type: Object,
    required: true,
  },
});

const initials = computed(() =>
  String(props.customer?.display_name || 'C')
    .trim()
    .slice(0, 2)
    .toUpperCase(),
);

const contactItems = computed(() => [
  { label: 'Phone', value: props.customer?.phone || '—' },
  {
    label: 'Website',
    value: props.customer?.website || '—',
    href: props.customer?.website || null,
  },
  {
    label: 'Timezone',
    value: `${props.customer?.timezone || '—'} · ${props.customer?.language || '—'}`,
  },
]);
</script>
