<template>
  <div ref="rootRef" class="relative">
    <div
      class="flex overflow-hidden border bg-white transition"
      :class="[
        size === 'lg' ? 'h-12 rounded-xl' : 'h-10 rounded-[12px]',
        disabled ? 'cursor-not-allowed bg-slate-50 opacity-60' : '',
        error
          ? 'border-rose-400 focus-within:border-rose-500'
          : open
            ? 'border-brand-500'
            : 'border-slate-200 focus-within:border-brand-500',
      ]"
    >
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1.5 border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none"
        :disabled="disabled"
        :aria-expanded="open"
        aria-haspopup="listbox"
        aria-label="Select country code"
        @click.stop="toggle"
      >
        <span aria-hidden="true">{{ selectedCountry?.flag }}</span>
        <span class="font-medium tabular-nums">{{ selectedCountry?.dialCode || '+1' }}</span>
        <ChevronDownIcon
          class="h-3.5 w-3.5 text-slate-400 transition"
          :class="open ? 'rotate-180' : ''"
        />
      </button>

      <input
        :id="id"
        :value="nationalNumber"
        type="tel"
        inputmode="tel"
        autocomplete="tel"
        spellcheck="false"
        maxlength="20"
        :name="name"
        :disabled="disabled"
        :placeholder="placeholder"
        :aria-invalid="error ? 'true' : 'false'"
        class="min-w-0 flex-1 border-0 bg-transparent px-3.5 text-sm text-slate-900 shadow-none outline-none placeholder:text-slate-400 focus:ring-0"
        @input="onNationalInput"
        @paste="onPaste"
      />
    </div>

    <div
      v-if="open"
      class="absolute left-0 z-[80] mt-1.5 w-[min(100%,22rem)] overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200"
      role="listbox"
      @click.stop
      @pointerdown.stop
    >
      <div class="border-b border-slate-100 p-2">
        <input
          ref="searchRef"
          v-model="query"
          type="search"
          autocomplete="off"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-900 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-0"
          placeholder="Search country or code"
          @keydown.esc.prevent="close"
        />
      </div>
      <ul class="max-h-64 overflow-y-auto py-1">
        <li v-if="filteredCountries.length === 0" class="px-3 py-2.5 text-sm text-slate-500">
          No countries found
        </li>
        <li
          v-for="country in filteredCountries"
          :key="country.iso"
          class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm transition"
          :class="
            country.iso === countryIso
              ? 'bg-brand-50 font-medium text-brand-700'
              : 'text-slate-700 hover:bg-slate-50'
          "
          role="option"
          :aria-selected="country.iso === countryIso"
          @mousedown.prevent="selectCountry(country.iso)"
        >
          <span class="w-6 text-base" aria-hidden="true">{{ country.flag }}</span>
          <span class="min-w-0 flex-1 truncate">{{ country.name }}</span>
          <span class="shrink-0 tabular-nums text-slate-500">{{ country.dialCode }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import {
  detectDefaultCountry,
  digitsOnly,
  getPhoneCountries,
  parseStoredPhone,
  toE164,
} from '@/utils/phone';
import { parsePhoneNumberFromString } from 'libphonenumber-js';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  defaultCountry: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: 'phone',
  },
  placeholder: {
    type: String,
    default: 'Phone number',
  },
  size: {
    type: String,
    default: 'lg',
    validator: (value) => ['md', 'lg'].includes(value),
  },
});

const emit = defineEmits(['update:modelValue']);

const countries = getPhoneCountries();
const fallbackCountry = computed(() => props.defaultCountry || detectDefaultCountry());
const countryIso = ref(fallbackCountry.value);
const nationalNumber = ref('');
const lastEmitted = ref('');
const open = ref(false);
const query = ref('');
const rootRef = ref(null);
const searchRef = ref(null);

const selectedCountry = computed(
  () => countries.find((country) => country.iso === countryIso.value) || countries[0],
);

const filteredCountries = computed(() => {
  const term = query.value.trim().toLowerCase();
  if (!term) {
    return countries;
  }

  return countries.filter((country) => {
    return (
      country.name.toLowerCase().includes(term) ||
      country.iso.toLowerCase().includes(term) ||
      country.dialCode.includes(term.replace(/\s/g, ''))
    );
  });
});

watch(
  () => props.modelValue,
  (value) => {
    if (value === lastEmitted.value) {
      return;
    }
    applyStored(value);
  },
  { immediate: true },
);

function applyStored(value) {
  const parsed = parseStoredPhone(value, fallbackCountry.value);
  countryIso.value = parsed.country;
  nationalNumber.value = parsed.nationalNumber;
  lastEmitted.value = value || '';
}

function emitValue() {
  lastEmitted.value = toE164(countryIso.value, nationalNumber.value);
  emit('update:modelValue', lastEmitted.value);
}

function onNationalInput(event) {
  const raw = event.target.value || '';
  if (raw.trim().startsWith('+') || raw.trim().startsWith('00')) {
    const parsed = parsePhoneNumberFromString(raw);
    if (parsed?.country) {
      countryIso.value = parsed.country;
      nationalNumber.value = parsed.nationalNumber || '';
      emitValue();
      return;
    }
  }

  nationalNumber.value = digitsOnly(raw).slice(0, 15);
  emitValue();
}

function onPaste(event) {
  const text = event.clipboardData?.getData('text') || '';
  if (!text.trim().startsWith('+') && !text.trim().startsWith('00')) {
    return;
  }

  event.preventDefault();
  const parsed = parsePhoneNumberFromString(text.trim());
  if (parsed?.country) {
    countryIso.value = parsed.country;
    nationalNumber.value = parsed.nationalNumber || '';
    emitValue();
  }
}

function selectCountry(iso) {
  countryIso.value = iso;
  close();
  emitValue();
}

function toggle() {
  if (props.disabled) {
    return;
  }
  open.value = !open.value;
  if (open.value) {
    query.value = '';
    nextTick(() => searchRef.value?.focus());
  }
}

function close() {
  open.value = false;
}

function onDocumentPointerDown(event) {
  if (!open.value || !rootRef.value) {
    return;
  }
  if (!rootRef.value.contains(event.target)) {
    close();
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown);
});
</script>
