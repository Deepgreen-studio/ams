<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6">
    <div class="flex items-start gap-4">
      <div
        class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-brand-700"
      >
        <img
          v-if="company?.logo_url"
          :src="company.logo_url"
          alt=""
          class="h-full w-full object-cover"
        />
        <span v-else class="text-lg font-semibold">{{ initials }}</span>
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="truncate text-xl font-semibold text-slate-900">{{ company?.company_name }}</h2>
          <StatusBadge :status="company?.status" />
        </div>
        <p class="mt-1 text-sm text-slate-500">
          {{ company?.legal_name || company?.email || '—' }}
        </p>
      </div>
    </div>
    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Country</dt>
        <dd class="text-sm text-slate-900">{{ company?.country || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Timezone</dt>
        <dd class="text-sm text-slate-900">{{ company?.timezone || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Currency</dt>
        <dd class="text-sm text-slate-900">{{ company?.currency || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wide text-slate-500">Website</dt>
        <dd class="text-sm text-slate-900">{{ company?.website || '—' }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from '@/modules/companies/components/StatusBadge.vue';

const props = defineProps({ company: { type: Object, default: null } });
const initials = computed(() => (props.company?.company_name || 'C').slice(0, 2).toUpperCase());
</script>
