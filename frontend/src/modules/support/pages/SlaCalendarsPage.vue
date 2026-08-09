<template>
  <div>
    <!-- <PageHeader title="Business Hours & Holidays" description="SLA calendars and holiday exceptions" /> -->
    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-900">Calendars</h3>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-if="store.calendars.length === 0" class="px-5 py-8 text-sm text-slate-500">
            No calendars configured.
          </div>
          <div v-for="calendar in store.calendars" :key="calendar.uuid" class="px-5 py-4">
            <p class="font-medium text-slate-900">{{ calendar.name }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ calendar.company?.company_name || 'Global' }} · {{ calendar.timezone }}
              <span v-if="calendar.is_default"> · Default</span>
            </p>
            <p class="mt-2 text-xs text-slate-600">
              Mon–Fri:
              {{ formatHours(calendar.business_hours?.monday) }}
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-900">Holidays</h3>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-if="store.holidays.length === 0" class="px-5 py-8 text-sm text-slate-500">
            No holidays configured.
          </div>
          <div v-for="holiday in store.holidays" :key="holiday.uuid" class="px-5 py-4">
            <p class="font-medium text-slate-900">{{ holiday.name }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ holiday.holiday_date }}
              <span v-if="holiday.is_recurring"> · Recurring yearly</span>
              · {{ holiday.company?.company_name || 'Global' }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();

onMounted(async () => {
  await Promise.all([store.fetchCalendars({ per_page: 50 }), store.fetchHolidays({ per_page: 50 })]);
});

function formatHours(window) {
  if (!Array.isArray(window) || window.length < 2) return 'Closed';
  return `${window[0]}–${window[1]}`;
}
</script>
