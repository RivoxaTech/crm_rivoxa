import axios from 'axios';

export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

api.interceptors.response.use(
    (r) => r,
    (e) => {
        if (e.response?.status === 401 && !location.pathname.startsWith('/login')) {
            window.location.assign('/login');
        }
        return Promise.reject(e);
    },
);

/** First useful message from a failed request. */
export function errMsg(e) {
    const d = e.response?.data;
    if (d?.errors) {
        return Object.values(d.errors).flat()[0];
    }

    return d?.message || e.message || 'Something went wrong';
}

export const pdfUrl = (id, download = false) => `/api/invoices/${id}/pdf${download ? '?download=1' : ''}`;
