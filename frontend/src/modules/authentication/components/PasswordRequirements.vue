<template>
  <div class="mt-3">
    <p class="text-xs font-bold uppercase tracking-wide text-zinc-900">Password must contain:</p>
    <ul class="mt-2 space-y-1">
      <li
        v-for="rule in rules"
        :key="rule.id"
        class="flex items-center gap-2 text-sm"
        :class="rule.met ? 'text-emerald-600' : 'text-red-600'"
      >
        <CheckIcon v-if="rule.met" class="h-4 w-4 shrink-0" />
        <XMarkIcon v-else class="h-4 w-4 shrink-0" />
        <span>{{ rule.label }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { evaluatePassword } from '@/modules/authentication/utils/passwordRules';

const props = defineProps({
  password: { type: String, default: '' },
});

const rules = computed(() => evaluatePassword(props.password));
</script>
