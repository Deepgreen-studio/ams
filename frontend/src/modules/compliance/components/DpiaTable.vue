<template>
  <div :class="framed ? 'overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100' : ''">
    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 6" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>
    <EmptyState
      v-else-if="!assessments.length"
      title="No assessments found"
      description="Try adjusting your filters or create a DPIA using the wizard."
      class="px-6 py-10 sm:px-8"
    >
      <template #action>
        <slot name="empty-action" />
      </template>
    </EmptyState>
    <div v-else class="scrollbar-light overflow-x-auto px-3">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-zinc-100">
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Assessment</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 md:table-cell">
              Template
            </th>
            <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 lg:table-cell">
              Risk
            </th>
            <th class="hidden px-5 py-3 text-left text-sm font-semibold text-zinc-500 xl:table-cell">
              Assignee
            </th>
            <th
              v-if="hasAnyAction"
              class="px-5 py-3 text-right text-sm font-semibold text-zinc-500"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in assessments"
            :key="item.uuid"
            class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
          >
            <td class="px-5 py-4">
              <RouterLink
                v-if="can('compliance.view')"
                :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
                class="font-medium text-slate-900 hover:text-brand-700"
              >
                {{ item.title }}
              </RouterLink>
              <p v-else class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ item.assessment_number }}
                <span v-if="item.company?.company_name"> · {{ item.company.company_name }}</span>
              </p>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 md:table-cell">
              {{ item.template_label || item.template_code || '—' }}
            </td>
            <td class="px-5 py-4">
              <DpiaStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-5 py-4 lg:table-cell">
              <div class="flex flex-wrap items-center gap-2">
                <BreachSeverityBadge
                  v-if="item.overall_risk_level"
                  :severity="item.overall_risk_level"
                  :label="item.overall_risk_level_label"
                />
                <span class="text-slate-600">{{ item.overall_risk_score ?? '—' }}</span>
              </div>
            </td>
            <td class="hidden px-5 py-4 text-slate-600 xl:table-cell">
              {{ item.assignee?.full_name || 'Unassigned' }}
            </td>
            <td v-if="hasAnyAction" class="px-5 py-4">
              <div class="relative flex justify-end">
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                  :aria-expanded="openMenuId === item.uuid"
                  aria-haspopup="menu"
                  aria-label="Open actions"
                  @click.stop="toggleMenu(item.uuid, $event)"
                >
                  <EllipsisVerticalIcon class="h-5 w-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <Teleport to="body">
    <div
      v-if="openMenuId && activeAssessment"
      class="fixed z-[80] w-44 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
      role="menu"
      :style="menuStyle"
      @click.stop
    >
      <RouterLink
        v-if="can('compliance.view')"
        :to="{ name: 'compliance.dpia.show', params: { id: activeAssessment.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <EyeIcon class="h-4 w-4 text-slate-400" />
        View
      </RouterLink>
      <RouterLink
        v-if="canContinue(activeAssessment)"
        :to="{ name: 'compliance.dpia.wizard.edit', params: { id: activeAssessment.uuid } }"
        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 transition hover:bg-zinc-50"
        role="menuitem"
        @click="closeMenu"
      >
        <PencilSquareIcon class="h-4 w-4 text-slate-400" />
        Continue wizard
      </RouterLink>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { EllipsisVerticalIcon, EyeIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';

const props = defineProps({
  assessments: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  framed: { type: Boolean, default: true },
});

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('compliance.view', 'compliance.update'));
const openMenuId = ref(null);
const menuStyle = ref({});

const activeAssessment = computed(
  () => props.assessments.find((item) => item.uuid === openMenuId.value) || null,
);

onMounted(() => {
  document.addEventListener('click', closeMenu);
  window.addEventListener('scroll', closeMenu, true);
  window.addEventListener('resize', closeMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu);
  window.removeEventListener('scroll', closeMenu, true);
  window.removeEventListener('resize', closeMenu);
});

function canContinue(item) {
  return can('compliance.update') && ['draft', 'in_progress', 'rejected'].includes(item?.status);
}

function toggleMenu(id, event) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const rect = event.currentTarget.getBoundingClientRect();
  const menuWidth = 176;
  const menuHeight = 8 + 2 * 36;
  const gap = 8;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUp = spaceBelow < menuHeight + gap;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;
  const left = Math.min(Math.max(8, rect.right - menuWidth), window.innerWidth - menuWidth - 8);

  menuStyle.value = {
    top: `${Math.max(8, top)}px`,
    left: `${left}px`,
  };
  openMenuId.value = id;
}

function closeMenu() {
  openMenuId.value = null;
}
</script>
