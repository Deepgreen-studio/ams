<template>
  <div class="rounded-[12px] bg-white p-6 sm:p-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="inline-flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[12px] bg-brand-50 text-base font-semibold text-brand-700"
      >
        <img
          v-if="logoSrc"
          :src="logoSrc"
          alt=""
          class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="truncate text-xl font-semibold tracking-tight text-slate-900">
            {{ company?.company_name }}
          </h2>
          <StatusBadge :status="company?.status || 'active'" />
        </div>
        <p class="mt-1 truncate text-sm text-slate-500">{{ subtitle }}</p>
        <p class="mt-1 text-sm text-slate-500">Owns applications and has customers.</p>
      </div>
    </div>

    <dl class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-[12px] bg-slate-50/60">
      <div class="grid grid-cols-[7.5rem_1fr] gap-3 px-3.5 py-3 sm:grid-cols-[8.5rem_1fr]">
        <dt class="text-xs font-medium text-slate-500">Currency</dt>
        <dd class="min-w-0 truncate text-sm font-medium text-slate-900">{{ currencyLabel }}</dd>
      </div>
    </dl>

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
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';
import { CURRENCY_OPTIONS } from '@/utils/localeOptions';
import { resolveMediaUrl } from '@/utils/mediaUrl';

const props = defineProps({
  company: {
    type: Object,
    default: null,
  },
});

const initials = computed(() => (props.company?.company_name || 'C').slice(0, 2).toUpperCase());
const logoSrc = computed(() => resolveMediaUrl(props.company?.logo_url || props.company?.logo || ''));

const subtitle = computed(() => {
  const legal = props.company?.legal_name;
  const email = props.company?.email;
  if (legal && legal !== props.company?.company_name) {
    return legal;
  }
  return email || legal || '-';
});

const currencyLabel = computed(() => {
  const code = props.company?.currency;
  if (!code) {
    return '-';
  }

  return CURRENCY_OPTIONS.find((option) => option.value === code)?.label || code;
});

const contactItems = computed(() => [
  { label: 'Email', value: props.company?.email || '-' },
  { label: 'Phone', value: props.company?.phone || '-' },
  {
    label: 'Website',
    value: props.company?.website || '-',
    href: props.company?.website || null,
  },
]);
</script>
