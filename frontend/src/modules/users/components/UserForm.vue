<template>
  <form class="space-y-8" @submit.prevent="onSubmit">
    <section v-if="layout === 'profile'" class="space-y-4">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Personal information</h3>
        <p class="mt-0.5 text-xs text-slate-500">Your name and contact details visible across AMS.</p>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <FormLabel required>First Name</FormLabel>
          <input
            v-model="form.first_name"
            type="text"
            required
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('first_name')"
          />
          <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
        </div>
        <div>
          <FormLabel required>Last Name</FormLabel>
          <input
            v-model="form.last_name"
            type="text"
            required
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('last_name')"
          />
          <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
        </div>
        <div>
          <FormLabel required>Email</FormLabel>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
            :class="fieldClass('email')"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
          <PhoneInput
            v-model="form.phone"
            :error="Boolean(fieldError('phone'))"
          />
          <p v-if="fieldError('phone')" class="mt-1 text-xs text-rose-600">{{ fieldError('phone') }}</p>
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

    <div v-else class="grid gap-x-10 gap-y-5 md:grid-cols-2">
      <div>
        <FormLabel required>First Name</FormLabel>
        <input
          v-model="form.first_name"
          type="text"
          required
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('first_name')"
        />
        <p v-if="errors.first_name" class="mt-1 text-xs text-rose-600">{{ errors.first_name[0] }}</p>
      </div>
      <div>
        <FormLabel required>Last Name</FormLabel>
        <input
          v-model="form.last_name"
          type="text"
          required
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('last_name')"
        />
        <p v-if="errors.last_name" class="mt-1 text-xs text-rose-600">{{ errors.last_name[0] }}</p>
      </div>
      <div>
        <FormLabel required>Email</FormLabel>
        <input
          v-model="form.email"
          type="email"
          required
          class="w-full h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 shadow-none focus:border-brand-500 focus:outline-none focus:ring-0"
          :class="fieldClass('email')"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-rose-600">{{ errors.email[0] }}</p>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
        <PhoneInput
          v-model="form.phone"
          :error="Boolean(fieldError('phone'))"
        />
        <p v-if="fieldError('phone')" class="mt-1 text-xs text-rose-600">{{ fieldError('phone') }}</p>
      </div>
      <div v-if="layout !== 'profile'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Company</label>
        <SearchableSelect
          v-model="form.company_id"
          :options="companySelectOptions"
          placeholder="Select a company"
          search-placeholder="Search company…"
          :button-class="companyButtonClass"
        />
        <p v-if="errors.company_id" class="mt-1 text-xs text-rose-600">{{ errors.company_id[0] }}</p>
      </div>
      <div v-if="showStatus">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
        <SelectBox
          v-model="form.status"
          size="lg"
          :options="statusOptions"
        />
      </div>
      <div v-if="layout !== 'profile'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Department</label>
        <SearchableSelect
          v-model="form.department_id"
          :options="departmentSelectOptions"
          :disabled="!form.company_id"
          :placeholder="form.company_id ? 'Select a department' : 'Select a company first'"
          search-placeholder="Search department…"
          :button-class="departmentButtonClass"
        />
        <p v-if="errors.department_id" class="mt-1 text-xs text-rose-600">{{ errors.department_id[0] }}</p>
      </div>
      <div v-if="layout !== 'profile'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Team</label>
        <SearchableSelect
          v-model="form.team_id"
          :options="teamSelectOptions"
          :disabled="!form.department_id"
          :placeholder="form.department_id ? 'Select a team' : 'Select a department first'"
          search-placeholder="Search team…"
        />
      </div>
      <div v-if="layout !== 'profile'">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
        <SearchableSelect
          v-model="form.location_id"
          :options="locationSelectOptions"
          :disabled="!form.company_id"
          :placeholder="form.company_id ? 'Select a location' : 'Select a company first'"
          search-placeholder="Search location…"
        />
      </div>
      <div v-if="showRole">
        <FormLabel required>Role</FormLabel>
        <SelectBox
          v-model="form.role"
          size="lg"
          placeholder="Select a role"
          :options="roleSelectOptions"
          :error="Boolean(fieldError('roles'))"
        />
        <p class="mt-1 text-xs text-slate-500">Each user has one role. Permissions are enforced on the server.</p>
        <p v-if="fieldError('roles')" class="mt-1 text-xs text-rose-600">{{ fieldError('roles') }}</p>
      </div>
    </div>

    <p
      v-if="layout !== 'profile' && !initial.uuid"
      class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600"
    >
      An invitation email is sent with a one-time link. The user sets a password, then signs in at the login page. The account stays Pending Invitation until then.
    </p>

    <p v-if="invitationNotice" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
      {{ invitationNotice }}
    </p>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-6">
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
import { computed, nextTick, reactive, ref, watch } from 'vue';
import FormLabel from '@/components/ui/FormLabel.vue';
import PhoneInput from '@/components/ui/PhoneInput.vue';
import SearchableSelect from '@/components/ui/SearchableSelect.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useToast } from '@/composables/useToast';
import { getTimezoneOptions, LANGUAGE_OPTIONS } from '@/utils/localeOptions';
import { isValidE164, PHONE_INVALID_MESSAGE } from '@/utils/phone';

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
  companyOptions: {
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
const localErrors = ref({});
const timezoneOptionsBase = getTimezoneOptions();

const invitationNotice = computed(() => {
  if (props.initial?.invitation_status === 'pending') {
    return 'This invitation is still pending. The account cannot be activated until the user sets a password from the invitation email.';
  }

  if (props.initial?.invitation_status === 'expired') {
    return 'This invitation has expired. Resend it before the user can sign in.';
  }

  return '';
});

const statusOptions = computed(() => {
  const locked = ['pending', 'expired'].includes(props.initial?.invitation_status);
  const options = [
    { value: 'inactive', label: 'Inactive' },
    { value: 'suspended', label: 'Suspended' },
  ];

  if (!locked) {
    options.unshift({ value: 'active', label: 'Active' });
  }

  return options;
});

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

const roleSelectOptions = computed(() =>
  (props.roleOptions || []).map((role) => ({
    value: role.name,
    label: role.display_name || role.name,
  }))
);

const companySelectOptions = computed(() =>
  (props.companyOptions || []).map((company) => ({
    value: company.uuid,
    label: company.company_name || company.legal_name || company.uuid,
  }))
);

const companyButtonClass = computed(() => {
  const base =
    'h-12 w-full rounded-xl border bg-white px-3.5 text-sm shadow-none focus:outline-none focus:ring-0';

  if (props.errors.company_id) {
    return `${base} border-rose-400 text-slate-900 focus:border-rose-500`;
  }

  return `${base} border-slate-200 text-slate-900 focus:border-brand-500`;
});

const departmentItems = ref([]);
const locationItems = ref([]);

const departmentSelectOptions = computed(() =>
  departmentItems.value.map((department) => ({
    value: department.uuid,
    label: department.department_name || department.name,
  })),
);

const teamSelectOptions = computed(() =>
  teamsForSelectedDepartment().map((team) => ({
    value: team.uuid,
    label: team.name,
  })),
);

async function applyCompanyLocale(companyId) {
  if (props.layout === 'profile' || !companyId) {
    return;
  }

  const company = (props.companyOptions || []).find((item) => item.uuid === companyId);
  if (!company) {
    return;
  }

  if (company.timezone) {
    form.timezone = company.timezone;
  }
  if (company.language) {
    form.language = company.language;
  }
  await nextTick();
}

function teamsForSelectedDepartment() {
  const department = departmentItems.value.find((item) => item.uuid === form.department_id);
  return department?.teams || [];
}

function applyDepartmentTeam() {
  if (!form.department_id || !departmentItems.value.length) {
    return;
  }

  const department = departmentItems.value.find((item) => item.uuid === form.department_id);
  if (!department) {
    return;
  }

  const teams = department.teams || [];
  if (teams.some((team) => team.uuid === form.team_id)) {
    return;
  }

  form.team_id = teams.length === 1 ? teams[0].uuid : '';
}

const locationSelectOptions = computed(() =>
  locationItems.value.map((location) => ({
    value: location.uuid,
    label: location.branch_name || location.name || location.uuid,
  })),
);

const departmentButtonClass = computed(() => {
  const base =
    'h-12 w-full rounded-xl border bg-white px-3.5 text-sm shadow-none focus:outline-none focus:ring-0';

  if (!form.company_id) {
    return `${base} border-slate-200 text-slate-400`;
  }

  if (props.errors.department_id) {
    return `${base} border-rose-400 text-slate-900 focus:border-rose-500`;
  }

  return `${base} border-slate-200 text-slate-900 focus:border-brand-500`;
});

watch(
  () => form.company_id,
  async (companyId, previous) => {
    if (previous && companyId !== previous) {
      form.department_id = '';
      form.team_id = '';
      form.location_id = '';
    }

    if (previous !== undefined && companyId && companyId !== previous) {
      applyCompanyLocale(companyId);
    }

    if (!companyId) {
      departmentItems.value = [];
      locationItems.value = [];
      form.department_id = '';
      form.team_id = '';
      form.location_id = '';
      return;
    }

    try {
      const [departments, locations] = await Promise.all([
        companyService.listDepartments({ company: companyId, per_page: 100, page: 1 }),
        companyService.listLocations({ company: companyId, per_page: 100, page: 1 }),
      ]);
      departmentItems.value = departments.data.data?.departments?.items ?? [];
      locationItems.value = locations.data.data?.locations?.items ?? [];
    } catch {
      departmentItems.value = [];
      locationItems.value = [];
    }
  },
  { immediate: true },
);

watch(
  () => [form.department_id, departmentItems.value],
  () => {
    applyDepartmentTeam();
  },
);

watch(
  () => props.initial,
  (value) => {
    Object.assign(form, createForm(value));
    applyDepartmentTeam();
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

watch(
  () => props.errors,
  () => {
    localErrors.value = {};
  },
  { deep: true }
);

function fieldError(field) {
  return localErrors.value?.[field]?.[0] || props.errors?.[field]?.[0] || '';
}

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
    timezone: value.timezone || 'Asia/Kolkata',
    language: value.language || 'en',
    company_id: value.company_id || '',
    department_id: value.department_id || '',
    team_id: value.team_id || '',
    location_id: value.location_id || '',
    status: value.status || 'active',
    role: resolveInitialRole(value),
    password: '',
    password_confirmation: '',
  };
}

function fieldClass(field) {
  return fieldError(field) ? 'border-rose-400 focus:border-rose-500 focus:ring-0' : '';
}

function onSubmit() {
  const payload = { ...form };
  const nextErrors = {};

  if (payload.phone && !isValidE164(payload.phone)) {
    nextErrors.phone = [PHONE_INVALID_MESSAGE];
  }

  if (props.showRole && !payload.role) {
    nextErrors.roles = ['The role field is required.'];
  }

  if (Object.keys(nextErrors).length) {
    localErrors.value = nextErrors;
    toast.error(Object.values(nextErrors)[0][0], 'Validation Failed');
    return;
  }

  localErrors.value = {};

  delete payload.password;
  delete payload.password_confirmation;
  delete payload.gender;
  delete payload.date_of_birth;

  if (!props.showStatus) {
    delete payload.status;
  }

  if (props.layout === 'profile') {
    delete payload.company_id;
    delete payload.department_id;
    delete payload.team_id;
    delete payload.location_id;
  } else {
    delete payload.timezone;
    delete payload.language;
  }

  if (props.layout !== 'profile' && !payload.company_id) {
    payload.company_id = null;
    payload.department_id = null;
    payload.team_id = null;
    payload.location_id = null;
  }

  if (props.layout !== 'profile' && !payload.department_id) {
    payload.department_id = null;
    payload.team_id = null;
  }

  if (props.layout !== 'profile' && !payload.team_id) {
    payload.team_id = null;
  }

  if (props.layout !== 'profile' && !payload.location_id) {
    payload.location_id = null;
  }

  if (props.showRole) {
    payload.roles = payload.role ? [payload.role] : [];
  }
  delete payload.role;

  if (!payload.phone) {
    payload.phone = null;
  }

  emit('submit', payload);
}
</script>
