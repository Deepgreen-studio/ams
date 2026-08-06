import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

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
