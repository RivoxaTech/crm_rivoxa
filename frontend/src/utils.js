export const fmt = (n) => {
    const v = Math.round((Number(n) || 0) * 100) / 100;

    return v.toLocaleString('en-US', { minimumFractionDigits: Number.isInteger(v) ? 0 : 2, maximumFractionDigits: 2 });
};
export const rs = (n) => `${Number(n) < 0 ? '−' : ''}Rs ${fmt(Math.abs(Number(n) || 0))}`;
export const short = (n) => {
    const a = Math.abs(n);

    return a >= 1e6 ? `${+(n / 1e6).toFixed(1)}M` : a >= 1e3 ? `${+(n / 1e3).toFixed(1)}k` : `${n}`;
};
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
export const date = (d) => {
    if (!d) return '—';
    const [y, m, day] = String(d).slice(0, 10).split('-');

    return `${+day} ${MONTHS[+m - 1]} ${y}`;
};
export const today = () => new Date().toISOString().slice(0, 10);
export const thisMonth = () => today().slice(0, 7);

export const KINDS = { website: 'Website', domain: 'Domain', hosting: 'Hosting', images: 'Images', addon: 'Add-on', other: 'Other' };
export const PALETTE = ['#141414', '#e3161e', '#6b7280', '#b7791f', '#1d6b3a', '#2563eb', '#7c3aed', '#0e7490', '#b8bcc4'];
export const METHODS = { bank: 'Bank transfer', cash: 'Cash', easypaisa: 'Easypaisa', jazzcash: 'JazzCash', cheque: 'Cheque', other: 'Other' };

export const blankItem = () => ({
    name: '', description: '', short_label: '', rate_text: '', kind: 'Other', billing: 'fixed', unit: '', unit_price: '',
    quantity: '', amount: '', cost: '', is_free: false, is_addon: false,
});

export const fromCatalog = (c) => ({
    ...blankItem(), name: c.name, description: c.description || '', short_label: c.short_label || '', rate_text: c.rate_text || '',
    kind: c.kind || c.name, billing: c.billing, unit: c.unit || '', unit_price: c.unit_price ?? '', cost: c.cost ?? '',
});

/** Editing an existing line: show the stored amount only when it was typed in by hand. */
export const toForm = (it) => ({
    ...blankItem(), ...it,
    description: it.description || '', short_label: it.short_label || '', rate_text: it.rate_text || '', unit: it.unit || '',
    quantity: it.quantity ?? '', cost: it.cost ?? '',
    amount: it.billing === 'per_unit' && it.quantity === null && it.amount !== null ? it.amount : '',
});

const num = (v) => (v === '' || v === null || v === undefined ? null : Number(v));
export const toPayload = (f) => ({
    ...f, unit_price: num(f.unit_price) ?? 0, quantity: num(f.quantity), amount: num(f.amount), cost: num(f.cost) ?? 0,
    description: f.description || null, short_label: f.short_label || null, rate_text: f.rate_text || null, unit: f.unit || null,
});

/** Legacy type keys (website, domain...) become readable; new types are already service names. */
export const kindLabel = (k) => KINDS[k] || k;

/** Live total of an unsaved item: null means "decided on delivery". */
export const calcAmount = (f) => {
    if (f.is_free) return 0;
    if (f.amount !== '' && f.amount !== null) return Number(f.amount);
    if (f.billing === 'fixed') return Number(f.unit_price) || 0;

    return f.quantity === '' || f.quantity === null ? null : (Number(f.unit_price) || 0) * Number(f.quantity);
};

export const rateOf = (it) => it.rate_text || (it.unit_price > 0 ? fmt(it.unit_price) + (it.billing === 'per_unit' ? ` / ${it.unit || 'unit'}` : '') : '—');
export const amountOf = (it) => (it.is_free ? 'Free' : it.amount === null ? 'On delivery' : rs(it.amount));

/** Same thresholds as the backend, for the badge colours. */
export const stateTone = (s) => ({ expired: 'red', soon: 'amber', ok: 'green', none: '' }[s] || '');
export const daysText = (d) => (d === null ? 'No date' : d < 0 ? `Expired ${-d}d ago` : d === 0 ? 'Expires today' : `${d} days left`);
