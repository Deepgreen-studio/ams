<template>
  <form class="space-y-8" @submit.prevent="onSubmit">
    <section v-if="layout === 'profile'" class="space-y-4">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Personal information</h3>
        <p class="mt-0.5 text-xs text-slate-500">Your name and contact details visible across AMS.</p>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">First name</label>
          <input
            v-model="form.first_name"
            type="text"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('first_name')"
          />
          <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Last name</label>
          <input
            v-model="form.last_name"
            type="text"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('last_name')"
          />
          <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('email')"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
          <input
            v-model="form.phone"
            type="text"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('phone')"
            placeholder="+15551234567"
          />
          <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Gender</label>
          <select
            v-model="form.gender"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          >
            <option value="">Prefer not to say / unset</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
            <option value="prefer_not_to_say">Prefer not to say</option>
          </select>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Date of birth</label>
          <input
            v-model="form.date_of_birth"
            type="date"
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          />
        </div>
      </div>
    </section>

    <section v-if="layout === 'profile'" class="space-y-4 border-t border-slate-100 pt-8">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Preferences</h3>
        <p class="mt-0.5 text-xs text-slate-500">Regional defaults used for dates, times, and content.</p>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Timezone</label>
          <SearchableSelect
            v-model="form.timezone"
            :options="timezoneOptions"
            placeholder="Select timezone"
            search-placeholder="Search timezone…"
          />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Language</label>
          <SearchableSelect
            v-model="form.language"
            :options="languageOptions"
            placeholder="Select language"
            search-placeholder="Search language…"
          />
        </div>
      </div>
    </section>

    <div v-else class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">First name</label>
        <input
          v-model="form.first_name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('first_name')"
        />
        <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
        <input
          v-model="form.last_name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('last_name')"
        />
        <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('email')"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input
          v-model="form.phone"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('phone')"
          placeholder="+15551234567"
        />
        <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Gender</label>
        <select
          v-model="form.gender"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        >
          <option value="">Prefer not to say / unset</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
          <option value="prefer_not_to_say">Prefer not to say</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Date of birth</label>
        <input
          v-model="form.date_of_birth"
          type="date"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label>
        <SearchableSelect
          v-model="form.timezone"
          :options="timezoneOptions"
          placeholder="Select timezone"
          search-placeholder="Search timezone…"
          button-class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Language</label>
        <SearchableSelect
          v-model="form.language"
          :options="languageOptions"
          placeholder="Select language"
          search-placeholder="Search language…"
          button-class="h-12 rounded-[12px] border border-slate-300 bg-white px-3 text-sm shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        />
      </div>

      <div v-if="showStatus">
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select
          v-model="form.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
        >
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div v-if="showRole">
        <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
        <select
          v-model="form.role"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('roles')"
        >
          <option value="">Select a role</option>
          <option
            v-for="role in roleOptions"
            :key="role.uuid || role.name"
            :value="role.name"
          >
            {{ role.display_name || role.name }}
          </option>
        </select>
        <p v-if="errors.roles" class="mt-1 text-xs text-rose-600">{{ errors.roles[0] }}</p>
      </div>
    </div>

    <div v-if="showPassword" class="grid gap-4 md:grid-cols-2">
      <div>
        <label for="user-form-password" class="mb-1 block text-sm font-medium text-slate-700">
          Password
          <span v-if="!requirePassword" class="font-normal text-slate-400">(optional)</span>
        </label>
        <PasswordInput
          id="user-form-password"
          v-model="form.password"
          autocomplete="new-password"
          :input-class="fieldClass('password')"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
      </div>
      <div>
        <label for="user-form-password-confirmation" class="mb-1 block text-sm font-medium text-slate-700">
          Confirm password
        </label>
        <PasswordInput
          id="user-form-password-confirmation"
          v-model="form.password_confirmation"
          autocomplete="new-password"
          :input-class="fieldClass('password')"
        />
      </div>
    </div>

    <div
      class="flex items-center justify-end gap-2"
      :class="layout === 'profile' ? 'border-t border-slate-100 pt-6' : ''"
    >
      <button
        type="button"
        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import PasswordInput from '@/modules/authentication/components/PasswordInput.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import { useToast } from '@/composables/useToast';
import { getTimezoneOptions, LANGUAGE_OPTIONS } from '@/utils/localeOptions';

const props = defineProps({
  initial: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  error: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  submitLabel: {
    type: String,
    default: 'Save',
  },
  showPassword: {
    type: Boolean,
    default: true,
  },
  requirePassword: {
    type: Boolean,
    default: false,
  },
  showStatus: {
    type: Boolean,
    default: true,
  },
  showRole: {
    type: Boolean,
    default: true,
  },
  roleOptions: {
    type: Array,
    default: () => [],
  },
  layout: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'profile'].includes(value),
  },
});

const emit = defineEmits(['submit', 'cancel']);
const toast = useToast();

const form = reactive(createForm(props.initial));
const timezoneOptionsBase = getTimezoneOptions();

const timezoneOptions = computed(() => {
  const current = form.timezone;
  if (current && !timezoneOptionsBase.some((option) => option.value === current)) {
    return [{ value: current, label: current }, ...timezoneOptionsBase];
  }
  return timezoneOptionsBase;
});

const languageOptions = computed(() => {
  const current = form.language;
  if (current && !LANGUAGE_OPTIONS.some((option) => option.value === current)) {
    return [{ value: current, label: current }, ...LANGUAGE_OPTIONS];
  }
  return LANGUAGE_OPTIONS;
});

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
  },
  { deep: true }
);

watch(
  () => props.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

function resolveInitialRole(value = {}) {
  if (value.role) {
    return value.role;
  }

  const roles = value.roles || [];
  if (!roles.length) {
    return '';
  }

  const first = roles[0];
  return typeof first === 'string' ? first : first?.name || '';
}

function createForm(value = {}) {
  return {
    first_name: value.first_name || '',
    last_name: value.last_name || '',
    email: value.email || '',
    phone: value.phone || '',
    gender: value.gender || '',
    date_of_birth: value.date_of_birth || '',
    timezone: value.timezone || 'UTC',
    language: value.language || 'en',
    status: value.status || 'active',
    role: resolveInitialRole(value),
    password: '',
    password_confirmation: '',
  };
}

function fieldClass(field) {
  return props.errors?.[field] ? 'border-rose-400 focus:border-rose-500 focus:ring-0' : '';
}

function onSubmit() {
  const payload = { ...form };

  if (!props.showPassword || (!props.requirePassword && !payload.password)) {
    delete payload.password;
    delete payload.password_confirmation;
  }

  if (!props.showStatus) {
    delete payload.status;
  }

  if (props.showRole) {
    payload.roles = payload.role ? [payload.role] : [];
  }
  delete payload.role;

  if (!payload.gender) {
    payload.gender = null;
  }

  if (!payload.phone) {
    payload.phone = null;
  }

  if (!payload.date_of_birth) {
    payload.date_of_birth = null;
  }

  emit('submit', payload);
}
</script>
