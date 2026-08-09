<template>
  <span
    class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold"
    :class="[sizeClass, colorClass]"
    :aria-label="name"
  >
    <img
      v-if="showImage"
      :src="src"
      :alt="name"
      class="h-full w-full object-cover"
      @error="onError"
    />
    <span v-else aria-hidden="true">{{ initials }}</span>
  </span>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { getUserInitials } from '@/utils/avatar';

const props = defineProps({
  src: {
    type: String,
    default: '',
  },
  name: {
    type: String,
    default: 'User',
  },
  firstName: {
    type: String,
    default: '',
  },
  lastName: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
  },
});

const imageFailed = ref(false);

watch(
  () => props.src,
  () => {
    imageFailed.value = false;
  },
);

const showImage = computed(() => Boolean(props.src) && !imageFailed.value);

const initials = computed(() =>
  getUserInitials({
    first_name: props.firstName,
    last_name: props.lastName,
    full_name: props.name,
    name: props.name,
  }),
);

const sizeClass = computed(() => {
  const map = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-16 w-16 text-lg',
    xl: 'h-20 w-20 text-xl',
  };
  return map[props.size] || map.md;
});

const colorClass = computed(() => 'bg-brand-50 text-brand-700');

function onError() {
  imageFailed.value = true;
}
</script>
