import axios from 'axios';

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

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const payload = error.response?.data ?? {
      success: false,
      message: 'Unexpected Error',
    };

    return Promise.reject(payload);
  }
);

export default api;
