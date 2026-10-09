import { computed, ref, unref, watch } from 'vue';

function messageOf(value) {
  if (Array.isArray(value)) return value[0] || '';
  return value == null ? '' : String(value);
}

function asMessages(value) {
  if (Array.isArray(value)) return value.filter((message) => message != null && message !== '');
  if (value == null || value === '') return [];
  return [String(value)];
}

function sameErrors(left, right) {
  const leftKeys = Object.keys(left);
  const rightKeys = Object.keys(right);
  if (leftKeys.length !== rightKeys.length) return false;
  return leftKeys.every((key) => messageOf(left[key]) === messageOf(right[key]));
}

function readErrors(source) {
  const value = typeof source === 'function' ? source() : unref(source);
  return value && typeof value === 'object' ? value : {};
}

function signature(errors) {
  const normalized = {};
  for (const [key, value] of Object.entries(errors || {})) {
    normalized[key] = messageOf(value);
  }
  return JSON.stringify(normalized);
}

function fieldToken(value) {
  if (typeof File !== 'undefined' && value instanceof File) {
    return `file:${value.name}:${value.size}:${value.lastModified}`;
  }
  if (Array.isArray(value) || (value && typeof value === 'object')) {
    try {
      return JSON.stringify(value);
    } catch {
      return String(value);
    }
  }
  return value ?? '';
}

function snapshot(form) {
  const source = unref(form) || {};
  const copy = {};
  for (const key of Object.keys(source)) {
    copy[key] = fieldToken(source[key]);
  }
  return copy;
}

function isFilled(token) {
  return token !== '' && token !== null && token !== undefined && token !== '[]' && token !== '{}';
}

/**
 * Keep field errors in sync with the form.
 * A client error disappears as soon as that field is valid.
 * A server error disappears once the user changes that field.
 * A "required" server error also disappears when the field already has a value.
 */
export function useFieldErrors(form, serverErrors = () => ({}), collectErrors = () => ({})) {
  const localErrors = ref({});
  const dismissedServerErrors = ref({});
  const baseline = ref(snapshot(form));
  let serverSignature = signature(readErrors(serverErrors));

  const displayErrors = computed(() => {
    const fresh = collectErrors() || {};
    const current = snapshot(form);
    const remote = readErrors(serverErrors);
    const server = {};

    for (const [key, value] of Object.entries(remote)) {
      if (dismissedServerErrors.value[key]) continue;

      const changed = baseline.value[key] !== current[key];
      const clientStillInvalid = Boolean(fresh[key]);
      const staleRequired = !clientStillInvalid
        && isFilled(current[key])
        && /required|please select|select an /i.test(messageOf(value));

      if (changed || staleRequired) continue;
      const messages = asMessages(value);
      if (messages.length) server[key] = messages;
    }

    const local = {};
    for (const key of Object.keys(localErrors.value)) {
      if (fresh[key]) local[key] = asMessages(fresh[key]);
    }

    return { ...server, ...local };
  });

  function fieldMessage(field) {
    return messageOf(displayErrors.value?.[field]);
  }

  function pruneLocalErrors() {
    if (!Object.keys(localErrors.value).length) return;
    const fresh = collectErrors() || {};
    const kept = {};
    for (const key of Object.keys(localErrors.value)) {
      if (fresh[key]) kept[key] = asMessages(fresh[key]);
    }
    if (!sameErrors(localErrors.value, kept)) localErrors.value = kept;
  }

  watch(form, () => {
    const current = snapshot(form);
    const remote = readErrors(serverErrors);
    const dismissed = { ...dismissedServerErrors.value };
    let serverChanged = false;

    for (const key of Object.keys(remote)) {
      if (!dismissed[key] && baseline.value[key] !== current[key]) {
        dismissed[key] = true;
        serverChanged = true;
      }
    }

    if (serverChanged) dismissedServerErrors.value = dismissed;
    pruneLocalErrors();
  }, { deep: true, flush: 'sync' });

  watch(
    () => signature(readErrors(serverErrors)),
    (next) => {
      if (next === serverSignature) return;
      serverSignature = next;
      localErrors.value = {};
      dismissedServerErrors.value = {};
      baseline.value = snapshot(form);
    },
  );

  function clearLocalErrors() {
    localErrors.value = {};
  }

  return {
    localErrors,
    displayErrors,
    fieldMessage,
    clearLocalErrors,
  };
}
