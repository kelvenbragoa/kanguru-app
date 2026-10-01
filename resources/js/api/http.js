import axios from 'axios';

const TOKEN_KEY = 'auth_token';

const http = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

http.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }
    return config;
});

let onUnauthorized = null;

export function setUnauthorizedHandler(handler) {
    onUnauthorized = handler;
}

http.interceptors.response.use(
    (response) => response,
    (error) => {
        const url = error.config?.url || '';
        const isLogin = url.includes('/auth/login');
        if (error.response?.status === 401 && !isLogin) {
            onUnauthorized?.();
        }
        return Promise.reject(error);
    }
);

export function apiPayload(response) {
    return response.data?.data ?? response.data;
}

export function unwrapPage(payload) {
    const inner = payload?.data ?? payload;
    if (Array.isArray(inner)) {
        return { items: inner, total: inner.length, page: 1, lastPage: 1 };
    }
    return {
        items: inner?.data ?? [],
        total: inner?.total ?? 0,
        page: inner?.current_page ?? 1,
        lastPage: inner?.last_page ?? 1,
        perPage: inner?.per_page ?? 15,
    };
}

export function apiError(error, fallback = 'Ocorreu um erro.') {
    const errors = error.response?.data?.errors;
    if (errors) {
        const first = Object.values(errors).flat()[0];
        if (first) return first;
    }
    return error.response?.data?.message || fallback;
}

export { TOKEN_KEY };
export default http;
