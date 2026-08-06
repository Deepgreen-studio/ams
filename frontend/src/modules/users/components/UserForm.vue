<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">First name</label>
        <input
          v-model="form.first_name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('first_name')"
        />
        <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
        <input
          v-model="form.last_name"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('last_name')"
        />
        <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('email')"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
        <input
          v-model="form.phone"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('phone')"
          placeholder="+15551234567"
        />
        <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">{{ errors.phone[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Gender</label>
        <select
          v-model="form.gender"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
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
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Timezone</label>
        <input
          v-model="form.timezone"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="UTC"
        />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Language</label>
        <input
          v-model="form.language"
          type="text"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          placeholder="en"
        />
      </div>

      <div v-if="showStatus">
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select
          v-model="form.status"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
        >
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
          <option value="pending">Pending</option>
        </select>
      </div>
    </div>

    <div v-if="showPassword" class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">
          Password
          <span v-if="!requirePassword" class="font-normal text-slate-400">(optional)</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('password')"
          autocomplete="new-password"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-rose-600">{{ errors.password[0] }}</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          :class="fieldClass('password')"
          autocomplete="new-password"
        />
      </div>
    </div>

    <div class="flex items-center justify-end gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        :disabled="loading"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';

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
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive(createForm(props.initial));

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
  },
  { deep: true }
);

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
    password: '',
    password_confirmation: '',
  };
}

function fieldClass(field) {
  return props.errors?.[field] ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100' : '';
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
