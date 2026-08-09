import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

/**
 * Lightweight global toast notifications.
 * @param {{ title?: string, message: string, type?: 'error'|'success'|'info', durationMs?: number }} options
 */
export function pushToast({ title = '', message, type = 'info', durationMs = 3000 }) {
  if (!message) {
    return null;
  }

  const id = ++toastId;
  const toast = { id, title, message, type };
  toasts.value = [...toasts.value, toast];

  window.setTimeout(() => {
    dismissToast(id);
  }, durationMs);

  return id;
}

export function dismissToast(id) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

export function useToast() {
  return {
    toasts,
    pushToast,
    dismissToast,
    error(message, title = 'Error') {
      return pushToast({ title, message, type: 'error', durationMs: 3000 });
    },
    success(message, title = 'Success') {
      return pushToast({ title, message, type: 'success', durationMs: 3000 });
    },
    info(message, title = '') {
      return pushToast({ title, message, type: 'info', durationMs: 3000 });
    },
  };
}
