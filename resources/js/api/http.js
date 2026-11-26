import axios from 'axios';

const TOKEN_KEY = 'auth_token';

const http = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

export function applyAuthToken(token) {
    if (token) {
        http.defaults.headers.common.Authorization = `Bearer ${token}`;
        http.defaults.headers.common['X-Kanguro-Authorization'] = `Bearer ${token}`;
    } else {
        delete http.defaults.headers.common.Authorization;
        delete http.defaults.headers.common['X-Kanguro-Authorization'];
    }
}

// Restaurar token se a página recarregar com sessão em localStorage
const existing = localStorage.getItem(TOKEN_KEY);
if (existing) {
    applyAuthToken(existing);
}

function setHeader(headers, key, value) {
    if (!headers) return;
    if (typeof headers.set === 'function') {
        headers.set(key, value);
        return;
    }
    headers[key] = value;
}

http.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
        setHeader(config.headers, 'Authorization', `Bearer ${token}`);
        // Fallback para hosts que removem Authorization (cPanel/CGI)
        setHeader(config.headers, 'X-Kanguro-Authorization', `Bearer ${token}`);
    }
    if (config.data instanceof FormData) {
        if (typeof config.headers?.delete === 'function') {
            config.headers.delete('Content-Type');
        } else if (config.headers) {
            delete config.headers['Content-Type'];
        }
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
