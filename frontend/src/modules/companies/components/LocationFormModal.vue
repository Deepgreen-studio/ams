<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="location-form-title"
    >
      <h3 id="location-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit location' : 'Add location' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{ isEdit ? 'Update branch location details.' : 'Create a branch location for this company.' }}
      </p>

      <form class="mt-5 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label
            for="location-branch-name"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >
            Branch name
          </label>
          <input
            id="location-branch-name"
            ref="nameInput"
            v-model="form.branch_name"
            type="text"
            maxlength="255"
            required
            autocomplete="off"
            class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="e.g. Head office"
            :disabled="loading"
            @keydown.esc.prevent="onCancel"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label
              for="location-city"
              class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >
              City
            </label>
            <input
              id="location-city"
              v-model="form.city"
              type="text"
              maxlength="255"
              autocomplete="off"
              class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="City"
              :disabled="loading"
            />
          </div>
          <div>
            <label
              for="location-country"
              class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >
              Country
            </label>
            <input
              id="location-country"
              v-model="form.country"
              type="text"
              maxlength="255"
              autocomplete="off"
              class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="Country"
              :disabled="loading"
            />
          </div>
        </div>

        <div>
          <label
            for="location-address"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >
            Address
            <span class="normal-case tracking-normal text-slate-400">(optional)</span>
          </label>
          <input
            id="location-address"
            v-model="form.address"
            type="text"
            maxlength="500"
            autocomplete="off"
            class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="Street address"
            :disabled="loading"
          />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label
              for="location-phone"
              class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >
              Phone
            </label>
            <input
              id="location-phone"
              v-model="form.phone"
              type="text"
              maxlength="50"
              autocomplete="off"
              class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="Phone"
              :disabled="loading"
            />
          </div>
          <div>
            <label
              for="location-email"
              class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >
              Email
            </label>
            <input
              id="location-email"
              v-model="form.email"
              type="email"
              maxlength="255"
              autocomplete="off"
              class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              placeholder="Email"
              :disabled="loading"
            />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
            Status
          </label>
          <SelectBox
            v-model="form.status"
            wrapper-class="w-full"
            size="lg"
            :options="statusOptions"
            :disabled="loading"
          />
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
            :disabled="loading"
            @click="onCancel"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="loading || !form.branch_name.trim()"
          >
            {{ submitLabel }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  location: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['submit', 'cancel']);

const nameInput = ref(null);
const form = reactive({
  branch_name: '',
  address: '',
  city: '',
  country: '',
  phone: '',
  email: '',
  status: 'active',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const isEdit = computed(() => Boolean(props.location?.uuid || props.location?.id));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Update location' : 'Add location';
});

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    form.branch_name = props.location?.branch_name || '';
    form.address = props.location?.address || '';
    form.city = props.location?.city || '';
    form.country = props.location?.country || '';
    form.phone = props.location?.phone || '';
    form.email = props.location?.email || '';
    form.status = props.location?.status || 'active';
    await nextTick();
    nameInput.value?.focus();
  },
);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit() {
  const branchName = form.branch_name.trim();
  if (!branchName || props.loading) return;

  emit('submit', {
    branch_name: branchName,
    address: form.address.trim() ? form.address.trim() : null,
    city: form.city.trim() ? form.city.trim() : null,
    country: form.country.trim() ? form.country.trim() : null,
    phone: form.phone.trim() ? form.phone.trim() : null,
    email: form.email.trim() ? form.email.trim() : null,
    status: form.status || 'active',
  });
}
</script>
