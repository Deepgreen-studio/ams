<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-slate-900">{{ customer.display_name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ customer.email }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <TypeBadge :type="customer.customer_type" />
        <StatusBadge :status="customer.status" />
      </div>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Owning company</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ customer.company?.company_name || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ customer.phone || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Website</dt>
        <dd class="mt-1 text-sm text-slate-900">
          <a
            v-if="customer.website"
            :href="customer.website"
            target="_blank"
            rel="noopener noreferrer"
            class="text-brand-700 hover:underline"
          >
            {{ customer.website }}
          </a>
          <span v-else>—</span>
        </dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Industry</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ customer.industry || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Country</dt>
        <dd class="mt-1 text-sm text-slate-900">{{ customer.country || '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">
          Timezone / Language
        </dt>
        <dd class="mt-1 text-sm text-slate-900">
          {{ customer.timezone || '—' }} · {{ customer.language || '—' }}
        </dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import StatusBadge from '@/modules/customers/components/StatusBadge.vue';
import TypeBadge from '@/modules/customers/components/TypeBadge.vue';

defineProps({
  customer: { type: Object, required: true },
});
</script>
