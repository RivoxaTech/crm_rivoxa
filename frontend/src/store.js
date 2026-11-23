import { reactive } from 'vue';
import { api } from './api';

export const state = reactive({ user: null, ready: false, settings: {}, partners: [], catalog: null, toasts: [], confirm: null });

/** The services list is used by every item form, so fetch it once and refresh after edits. */
export async function loadCatalog(force = false) {
    if (force || !state.catalog) state.catalog = (await api.get('/catalog')).data;

    return state.catalog;
}

export function toast(message, type = 'ok') {
    const id = Date.now() + Math.random();
    state.toasts.push({ id, message, type });
    setTimeout(() => (state.toasts = state.toasts.filter((t) => t.id !== id)), type === 'error' ? 6000 : 3200);
}

/** await askConfirm({ title, message, ok, danger }) -> true | false */
export function askConfirm(opts) {
    return new Promise((resolve) => (state.confirm = { ok: 'Confirm', danger: false, ...opts, resolve }));
}
