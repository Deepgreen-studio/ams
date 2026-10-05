import axios from 'axios';
import { expireClientSession, isPublicAuthRequest, isSessionAuthenticationFailure } from '@/services/sessionGuard';
import { dateTimeLocalToUtcIso } from '@/utils/appTimezone';

const apiBaseURL = import.meta.env.VITE_API_BASE_URL || '';
const usesRemoteApi = /^https?:\/\//i.test(apiBaseURL);

const api = axios.create({
  baseURL: `${apiBaseURL}/api/v1`,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
  withXSRFToken: true,
});

export function setAuthToken(token) {
  if (token) {
    api.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete api.defaults.headers.common.Authorization;
  }
}

export async function ensureCsrfCookie() {
  // Remote SPA → API hosts cannot share XSRF cookies. Auth is Bearer-token
  // based and Laravel already excludes api/* from CSRF validation.
  if (usesRemoteApi) {
    return;
  }

  await axios.get(`${apiBaseURL}/sanctum/csrf-cookie`, {
    withCredentials: true,
    withXSRFToken: true,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  });
}

function convertDateTimeLocals(value) {
  if (typeof value === 'string') {
    return dateTimeLocalToUtcIso(value);
  }

  if (Array.isArray(value)) {
    return value.map((item) => convertDateTimeLocals(item));
  }

  if (typeof FormData !== 'undefined' && value instanceof FormData) {
    const next = new FormData();
    value.forEach((item, key) => {
      next.append(key, typeof item === 'string' ? convertDateTimeLocals(item) : item);
    });
    return next;
  }

  if (value && typeof value === 'object' && !(value instanceof Date) && !(typeof Blob !== 'undefined' && value instanceof Blob)) {
    return Object.fromEntries(
      Object.entries(value).map(([key, item]) => [key, convertDateTimeLocals(item)]),
    );
  }

  return value;
}

api.interceptors.request.use((config) => {
  if (config.data) {
    config.data = convertDateTimeLocals(config.data);
  }

  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      !isPublicAuthRequest(error.config) &&
      isSessionAuthenticationFailure(error)
    ) {
      expireClientSession(error.response?.data?.message);
    }

    const payload = error.response?.data ?? {
      success: false,
      message: 'Unexpected Error',
    };

    return Promise.reject(payload);
  }
);

export default api;
