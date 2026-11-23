<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ItemFields from '../components/ItemFields.vue';
import ExpenseForm from '../components/ExpenseForm.vue';
import InvoiceActions from '../components/InvoiceActions.vue';
import { api, errMsg } from '../api';
import { askConfirm, loadCatalog, state, toast } from '../store';
import { METHODS, amountOf, blankItem, date, fmt, fromCatalog, rateOf, rs, thisMonth, today, toForm, toPayload } from '../utils';

const route = useRoute();
const router = useRouter();
const d = ref(null);
const tab = ref('items');
const catalog = computed(() => state.catalog || []);
const busy = ref(false);

const load = async () => {
    d.value = (await api.get(`/projects/${route.params.id}`)).data;
    if (d.value.project.status === 'closed' && tab.value === 'items' && !loaded) tab.value = 'split';
    loaded = true;
};
let loaded = false;
onMounted(async () => {
    await load();
    loadCatalog();
});

const p = computed(() => d.value.project);
const s = computed(() => d.value.summary);
const closed = computed(() => p.value.status === 'closed');
const paidPct = computed(() => (s.value.billed ? Math.min(100, (s.value.paid / s.value.billed) * 100) : 0));

async function run(fn, ok) {
    busy.value = true;
    try {
        await fn();
        if (ok) toast(ok);
        await load();
        return true;
    } catch (e) {
        toast(errMsg(e), 'error');
        return false;
    } finally {
        busy.value = false;
    }
}

/* ---------- items ---------- */
const itemForm = ref(null);
const pick = ref('');
function addItem(addon = false) {
    const c = catalog.value.find((x) => x.id === pick.value);
    itemForm.value = { ...(c ? fromCatalog(c) : blankItem()), is_addon: addon };
    pick.value = '';
}
async function saveItem() {
    const f = itemForm.value;
    const ok = await run(() => (f.id ? api.put(`/items/${f.id}`, toPayload(f)) : api.post(`/projects/${p.value.id}/items`, toPayload(f))), 'Item saved');
    if (ok) itemForm.value = null;
}
async function delItem(it) {
    if (await askConfirm({ title: 'Remove item', message: `Remove "${it.name}" from this quote?`, ok: 'Remove', danger: true })) run(() => api.delete(`/items/${it.id}`), 'Item removed');
}

/* ---------- payments ---------- */
const payForm = ref(null);
const file = ref(null);
const newPayment = () => (payForm.value = { id: null, amount: s.value.balance > 0 ? s.value.balance : '', method: 'bank', paid_at: today(), label: '', reference: '', note: '', has_receipt: false });
async function savePayment() {
    const f = payForm.value;
    const body = new FormData();
    ['amount', 'method', 'paid_at', 'label', 'reference', 'note'].forEach((k) => body.append(k, f[k] ?? ''));
    if (file.value) body.append('receipt', file.value);
    const ok = await run(() => (f.id ? api.post(`/payments/${f.id}`, body) : api.post(`/projects/${p.value.id}/payments`, body)), 'Payment saved');
    if (ok) payForm.value = file.value = null;
}
async function delPayment(x) {
    if (await askConfirm({ title: 'Delete payment', message: `Delete the ${rs(x.amount)} payment?`, ok: 'Delete', danger: true })) run(() => api.delete(`/payments/${x.id}`), 'Payment deleted');
}

/* ---------- expenses ---------- */
const expForm = ref(null);
const reimburse = async (e) => (await askConfirm({ title: 'Reimburse partner', message: `Pay ${rs(e.amount)} back to ${e.partner?.name} from the company account?`, ok: 'Reimburse' })) && run(() => api.post(`/expenses/${e.id}/reimburse`), 'Reimbursed');
const undo = (e) => run(() => api.delete(`/expenses/${e.id}/reimburse`), 'Reimbursement undone');
async function delExpense(e) {
    if (await askConfirm({ title: 'Delete expense', message: `Delete "${e.title}"?`, ok: 'Delete', danger: true })) run(() => api.delete(`/expenses/${e.id}`), 'Expense deleted');
}

/* ---------- invoices ---------- */
const inv = ref(null);
async function openInvoice() {
    const period = p.value.type === 'monthly' ? thisMonth() : null;
    inv.value = { period, ...(await fetchPreview(period)) };
}
async function fetchPreview(period) {
    const { data } = await api.get(`/projects/${p.value.id}/invoice-preview`, { params: { period } });

    return { snap: data.snapshot, issue_date: data.issue_date, payment_terms: data.payment_terms, note: data.snapshot.note, payment_record: data.snapshot.payment_record };
}
watch(() => inv.value?.period, async (v, o) => {
    if (inv.value && o !== undefined && v) Object.assign(inv.value, await fetchPreview(v));
});
async function createInvoice() {
    const f = inv.value;
    const ok = await run(() => api.post(`/projects/${p.value.id}/invoices`, { issue_date: f.issue_date, payment_terms: f.payment_terms, period: f.period, note: f.note, payment_record: f.payment_record }), 'Invoice created');
    if (ok) {
        inv.value = null;
        tab.value = 'invoices';
    }
}
async function delInvoice(i) {
    if (await askConfirm({ title: 'Delete invoice', message: `Delete invoice ${i.number}? Its number will not be reused unless it is the latest one.`, ok: 'Delete', danger: true })) run(() => api.delete(`/invoices/${i.id}`), 'Invoice deleted');
}
const billMonth = ref(thisMonth());
const billPeriod = () => run(() => api.post(`/projects/${p.value.id}/bill-period`, { period: billMonth.value }), 'Month billed');

/* ---------- close / edit ---------- */
const closing = ref(false);
async function closeProject() {
    const ok = await run(() => api.post(`/projects/${p.value.id}/close`), 'Project closed. Profit shares are ready to pay.');
    if (ok) {
        closing.value = false;
        tab.value = 'split';
    }
}
async function reopen() {
    if (await askConfirm({ title: 'Reopen project', message: 'This removes the profit shares so you can make changes. Continue?', ok: 'Reopen' })) {
        if (await run(() => api.post(`/projects/${p.value.id}/reopen`), 'Project reopened')) tab.value = 'items';
    }
}
async function removeProject() {
    if (!(await askConfirm({ title: 'Delete project', message: 'Delete this project with all its items, payments, expenses and invoices?', ok: 'Delete', danger: true }))) return;
    try {
        await api.delete(`/projects/${p.value.id}`);
        router.push('/projects');
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
const shareStatus = (x) => (x.paid_amount <= 0 ? ['Unpaid', 'amber'] : x.paid_amount >= x.amount ? ['Paid', 'green'] : ['Part paid', 'blue']);
</script>

<template>
    <div v-if="!d" class="empty">Loading…</div>
    <template v-else>
        <router-link :to="`/clients/${p.client_id}`" class="back"><Icon name="back" :size="16" /> {{ p.client.name }}</router-link>
        <div class="page-head">
            <div>
                <h1>{{ p.title }}</h1>
                <p class="sub row wrap" style="gap: 8px; margin-top: 8px">
                    <Badge :tone="closed ? 'dark' : 'green'">{{ closed ? 'Closed' : 'Active' }}</Badge>
                    <Badge>{{ p.type === 'monthly' ? 'Monthly' : 'One-time' }}</Badge>
                    <Badge tone="blue">{{ p.split_mode === 'half' ? '50% partners · 50% company' : 'Equal three ways' }}</Badge>
                    <span>Started {{ date(p.start_date) }}</span>
                </p>
            </div>
            <div class="row wrap">
                <button v-if="!closed" class="btn" @click="router.push(`/projects/${p.id}/edit`)"><Icon name="edit" :size="15" /> Edit</button>
                <button v-if="!closed" class="btn btn-danger" @click="removeProject"><Icon name="trash" :size="15" /></button>
                <button class="btn" :disabled="!d.items.length" @click="openInvoice"><Icon name="file" :size="15" /> Generate invoice</button>
                <button v-if="!closed" class="btn btn-dark" @click="closing = true"><Icon name="lock" :size="15" /> Close project</button>
                <button v-else class="btn" @click="reopen"><Icon name="unlock" :size="15" /> Reopen</button>
            </div>
        </div>

        <div class="card">
            <div class="summary">
                <div><div class="l">Quoted total</div><div class="v">{{ rs(s.billed) }}</div><div v-if="s.pending_items" class="small red">+ {{ s.pending_items }} on delivery</div></div>
                <div><div class="l">Paid</div><div class="v green">{{ rs(s.paid) }}</div><div class="bar" style="margin-top: 8px"><i :style="{ width: paidPct + '%', background: '#1d6b3a' }"></i></div></div>
                <div><div class="l">Balance due</div><div class="v" :class="{ red: s.balance > 0 }">{{ rs(s.balance) }}</div></div>
                <div><div class="l">Expenses</div><div class="v">{{ rs(s.expenses) }}</div></div>
                <div><div class="l">{{ closed ? 'Final profit' : 'Expected profit' }}</div><div class="v">{{ rs(closed ? p.final_profit : s.profit) }}</div></div>
            </div>
        </div>

        <div v-if="!closed" class="card card-pad mt">
            <div class="row spread wrap" style="gap: 14px">
                <div><div class="lbl" style="margin: 0">Profit so far</div><div class="muted small">Quoted {{ rs(s.billed) }} − expenses {{ rs(s.expenses) }} = <b>{{ rs(s.profit) }}</b>. Shares are paid out after the project is closed.</div></div>
                <div class="row wrap" style="gap: 22px">
                    <div v-for="x in d.split_preview" :key="x.label"><div class="muted small">{{ x.label }} · {{ x.percent }}%</div><b>{{ rs(x.amount) }}</b></div>
                </div>
            </div>
        </div>

        <div v-if="s.pending_items && !closed" class="alert mt"><Icon name="alert" /><div>{{ s.pending_items }} item(s) are charged on delivery. Open the item and enter the quantity once delivered, then generate the final invoice.</div></div>

        <div class="tabs mt-lg">
            <button class="tab" :class="{ active: tab === 'items' }" @click="tab = 'items'">Items<span class="n">{{ d.items.length }}</span></button>
            <button class="tab" :class="{ active: tab === 'payments' }" @click="tab = 'payments'">Payments<span class="n">{{ d.payments.length }}</span></button>
            <button class="tab" :class="{ active: tab === 'expenses' }" @click="tab = 'expenses'">Expenses<span class="n">{{ d.expenses.length }}</span></button>
            <button class="tab" :class="{ active: tab === 'invoices' }" @click="tab = 'invoices'">Invoices<span class="n">{{ d.invoices.length }}</span></button>
            <button v-if="closed" class="tab" :class="{ active: tab === 'split' }" @click="tab = 'split'">Profit split</button>
        </div>

        <!-- ITEMS -->
        <div v-if="tab === 'items'" class="card">
            <div v-if="!closed" class="card-head">
                <h3>Quoted items</h3>
                <div class="row">
                    <select v-model="pick" class="select" style="width: 230px" @change="addItem(false)"><option value="">+ Add from services…</option><option v-for="c in catalog" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                    <button class="btn" @click="addItem(false)"><Icon name="plus" :size="15" /> Custom</button>
                    <button class="btn" @click="addItem(true)"><Icon name="plus" :size="15" /> Add-on</button>
                </div>
            </div>
            <div v-if="!d.items.length" class="empty"><b>No items yet</b>Add the website, domain, hosting and anything else you quoted.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>#</th><th>Item</th><th class="num">Rate</th><th class="num">Amount</th><th class="num">Our cost</th><th class="num">Profit</th><th v-if="!closed"></th></tr></thead>
                    <tbody>
                        <tr v-for="(it, i) in d.items" :key="it.id">
                            <td class="muted">{{ String(i + 1).padStart(2, '0') }}</td>
                            <td><b>{{ it.name }}</b> <Badge v-if="it.is_addon" tone="blue">Add-on</Badge><div class="sub">{{ it.description }}<span v-if="it.period"> · {{ it.period }}</span></div></td>
                            <td class="num">{{ rateOf(it) }}</td>
                            <td class="num strong" :class="{ red: it.amount === null && !it.is_free }">{{ amountOf(it) }}</td>
                            <td class="num muted">{{ it.cost ? rs(it.cost) : '—' }}</td>
                            <td class="num" :class="it.amount === null ? 'faint' : it.amount - it.cost >= 0 ? 'green' : 'red'">{{ it.amount === null ? '—' : rs(it.amount - it.cost) }}</td>
                            <td v-if="!closed" class="actions">
                                <button v-if="it.amount === null && !it.is_free" class="btn btn-sm" @click="itemForm = toForm(it)">Set quantity</button>
                                <button class="btn btn-sm btn-icon" @click="itemForm = toForm(it)"><Icon name="edit" :size="14" /></button>
                                <button class="btn btn-sm btn-icon btn-danger" @click="delItem(it)"><Icon name="trash" :size="14" /></button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot><tr><td colspan="3">Total</td><td class="num">{{ rs(s.billed) }}</td><td class="num">{{ rs(d.items.reduce((a, i) => a + (+i.cost || 0), 0)) }}</td><td class="num">{{ rs(s.billed - d.items.reduce((a, i) => a + (+i.cost || 0), 0)) }}</td><td v-if="!closed"></td></tr></tfoot>
                </table>
            </div>
        </div>

        <!-- PAYMENTS -->
        <div v-if="tab === 'payments'" class="card">
            <div v-if="!closed" class="card-head"><h3>Payments received</h3><button class="btn btn-primary" @click="newPayment"><Icon name="plus" :size="15" /> Record payment</button></div>
            <div v-if="!d.payments.length" class="empty"><b>No payments yet</b>Record the advance or any amount the client pays.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>Date</th><th>Details</th><th>Method</th><th>Receipt</th><th class="num">Amount</th><th v-if="!closed"></th></tr></thead>
                    <tbody>
                        <tr v-for="x in d.payments" :key="x.id">
                            <td>{{ date(x.paid_at) }}</td>
                            <td><b>{{ x.label || 'Payment' }}</b><div class="sub">{{ x.reference || x.note }}</div></td>
                            <td>{{ METHODS[x.method] }}</td>
                            <td><a v-if="x.has_receipt" :href="`/api/payments/${x.id}/receipt`" target="_blank" class="row small" style="gap: 5px"><Icon name="clip" :size="14" /> View</a><span v-else class="faint">—</span></td>
                            <td class="num strong green">{{ rs(x.amount) }}</td>
                            <td v-if="!closed" class="actions"><button class="btn btn-sm btn-icon" @click="payForm = { ...x, label: x.label || '', reference: x.reference || '', note: x.note || '' }"><Icon name="edit" :size="14" /></button> <button class="btn btn-sm btn-icon btn-danger" @click="delPayment(x)"><Icon name="trash" :size="14" /></button></td>
                        </tr>
                    </tbody>
                    <tfoot><tr><td colspan="4">Total received</td><td class="num">{{ rs(s.paid) }}</td><td v-if="!closed"></td></tr></tfoot>
                </table>
            </div>
        </div>

        <!-- EXPENSES -->
        <div v-if="tab === 'expenses'" class="card">
            <div v-if="!closed" class="card-head"><h3>Project expenses</h3><button class="btn btn-primary" @click="expForm = {}"><Icon name="plus" :size="15" /> Add expense</button></div>
            <div v-if="!d.expenses.length" class="empty"><b>No expenses</b>Costs of items (domain, hosting…) appear here automatically. Add anything else we spent on this client.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>Date</th><th>Expense</th><th>Paid by</th><th class="num">Amount</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="e in d.expenses" :key="e.id">
                            <td>{{ date(e.spent_on) }}</td>
                            <td><b>{{ e.title }}</b><div class="sub">{{ e.reason }}</div></td>
                            <td>
                                <template v-if="e.paid_by === 'company'">Company account</template>
                                <template v-else>{{ e.partner?.name }} <Badge :tone="e.reimbursed_on ? 'green' : 'amber'">{{ e.reimbursed_on ? 'Reimbursed ' + date(e.reimbursed_on) : 'To reimburse' }}</Badge></template>
                            </td>
                            <td class="num strong">{{ rs(e.amount) }}</td>
                            <td class="actions">
                                <template v-if="e.paid_by === 'partner'">
                                    <button v-if="!e.reimbursed_on" class="btn btn-sm" @click="reimburse(e)">Reimburse</button>
                                    <button v-else-if="!closed" class="btn btn-sm btn-ghost" @click="undo(e)">Undo</button>
                                </template>
                                <template v-if="!closed && !e.reimbursed_on">
                                    <button class="btn btn-sm btn-icon" @click="expForm = { ...e }"><Icon name="edit" :size="14" /></button>
                                    <button class="btn btn-sm btn-icon btn-danger" @click="delExpense(e)"><Icon name="trash" :size="14" /></button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot><tr><td colspan="3">Total expenses</td><td class="num">{{ rs(s.expenses) }}</td><td></td></tr></tfoot>
                </table>
            </div>
        </div>

        <!-- INVOICES -->
        <div v-if="tab === 'invoices'" class="stack">
            <div v-if="p.type === 'monthly' && !closed" class="card card-pad row wrap">
                <div class="grow"><b>Bill a month</b><p class="muted small">Adds the monthly lines for that month and creates its invoice.</p></div>
                <input v-model="billMonth" type="month" class="input" style="width: 170px" />
                <button class="btn btn-primary" :disabled="busy" @click="billPeriod">Bill this month</button>
                <span v-if="p.auto_generate" class="badge green">Auto on day {{ p.billing_day }}</span>
            </div>
            <div class="card">
                <div class="card-head"><h3>Invoices</h3><button class="btn" :disabled="!d.items.length" @click="openInvoice"><Icon name="plus" :size="15" /> Generate invoice</button></div>
                <div v-if="!d.invoices.length" class="empty"><b>No invoices yet</b>Generate one to send it to the client.</div>
                <div v-else class="table-wrap">
                    <table class="t">
                        <thead><tr><th>Invoice</th><th>Sent</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="i in d.invoices" :key="i.id">
                                <td><b>{{ i.number }}</b><div class="sub">{{ date(i.issue_date) }}{{ i.period ? ' · ' + i.period : '' }}</div></td>
                                <td><Badge v-if="i.whatsapp_at" tone="green">WhatsApp</Badge> <Badge v-if="i.emailed_at" tone="blue">Email</Badge><span v-if="!i.whatsapp_at && !i.emailed_at" class="faint">Not sent</span></td>
                                <td class="num">{{ rs(i.subtotal) }}</td><td class="num green">{{ rs(i.paid) }}</td><td class="num strong">{{ rs(i.balance) }}</td>
                                <td class="actions"><InvoiceActions :invoice="i" @changed="load"><button class="btn btn-sm btn-icon btn-danger" title="Delete" @click="delInvoice(i)"><Icon name="trash" :size="14" /></button></InvoiceActions></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PROFIT SPLIT -->
        <div v-if="tab === 'split' && closed" class="card">
            <div class="card-head"><h3>Profit split · closed {{ date(p.closed_at) }}</h3><span class="muted small">Revenue {{ rs(p.final_revenue) }} − expenses {{ rs(p.final_expenses) }} = <b>{{ rs(p.final_profit) }}</b></span></div>
            <div class="table-wrap">
                <table class="t">
                    <thead><tr><th>Who</th><th class="num">Share</th><th class="num">Amount</th><th class="num">Paid out</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-for="x in d.shares" :key="x.id">
                            <td><b>{{ x.party === 'company' ? 'Company' : x.partner?.name }}</b></td>
                            <td class="num">{{ x.percent }}%</td>
                            <td class="num strong">{{ rs(x.amount) }}</td>
                            <td class="num">{{ x.party === 'company' ? '—' : rs(x.paid_amount) }}</td>
                            <td>
                                <Badge v-if="x.party === 'company'" tone="dark">Stays in company account</Badge>
                                <router-link v-else :to="`/partners/${x.partner_id}`"><Badge :tone="shareStatus(x)[1]">{{ shareStatus(x)[0] }}</Badge></router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-pad muted small">Pay partners from their page: <router-link v-for="x in d.shares.filter((y) => y.party === 'partner')" :key="x.id" :to="`/partners/${x.partner_id}`" class="strong" style="margin-right: 12px; text-decoration: underline">{{ x.partner?.name }}</router-link></div>
        </div>

        <!-- MODALS -->
        <Modal v-if="itemForm" :title="itemForm.id ? 'Edit item' : itemForm.is_addon ? 'Add an add-on' : 'Add item'" @close="itemForm = null">
            <form id="item-form" @submit.prevent="saveItem"><ItemFields v-model="itemForm" /></form>
            <template #footer><button class="btn" @click="itemForm = null">Cancel</button><button class="btn btn-primary" form="item-form" :disabled="busy">Save item</button></template>
        </Modal>

        <Modal v-if="payForm" :title="payForm.id ? 'Edit payment' : 'Record payment'" @close="payForm = null">
            <form id="pay-form" class="form-grid" @submit.prevent="savePayment">
                <div class="field"><label>Amount (Rs)</label><input v-model="payForm.amount" type="number" min="0" step="any" class="input" required autofocus /><p v-if="!payForm.id && s.balance > 0" class="hint">Balance due is {{ rs(s.balance) }}</p></div>
                <div class="field"><label>Date</label><input v-model="payForm.paid_at" type="date" class="input" required /></div>
                <div class="field"><label>Method</label><select v-model="payForm.method" class="select"><option v-for="(l, k) in METHODS" :key="k" :value="k">{{ l }}</option></select></div>
                <div class="field"><label>Label on invoice</label><input v-model="payForm.label" class="input" placeholder="Advance paid" /></div>
                <div class="field span-2"><label>Reference / note</label><input v-model="payForm.reference" class="input" placeholder="Transaction ID, cheque no…" /></div>
                <div class="field span-2"><label>Receipt (optional photo or PDF)</label><input type="file" class="input" accept="image/*,.pdf" @change="file = $event.target.files[0]" /><p v-if="payForm.has_receipt" class="hint">A receipt is already attached. Choosing a file replaces it.</p></div>
            </form>
            <template #footer><button class="btn" @click="payForm = null">Cancel</button><button class="btn btn-primary" form="pay-form" :disabled="busy">Save payment</button></template>
        </Modal>

        <ExpenseForm v-if="expForm" :project-id="p.id" :expense="expForm.id ? expForm : null" @close="expForm = null" @saved="load" />

        <Modal v-if="inv" title="Generate invoice" wide @close="inv = null">
            <form id="inv-form" class="grid g2" @submit.prevent="createInvoice">
                <div class="stack">
                    <div v-if="p.type === 'monthly'" class="field"><label>Billing month</label><input v-model="inv.period" type="month" class="input" required /></div>
                    <div class="form-grid">
                        <div class="field"><label>Issue date</label><input v-model="inv.issue_date" type="date" class="input" required /></div>
                        <div class="field"><label>Payment terms</label><input v-model="inv.payment_terms" class="input" list="terms" required /><datalist id="terms"><option>Balance on delivery</option><option>Due on receipt</option><option>Due in 7 days</option><option>Due in 15 days</option></datalist></div>
                    </div>
                    <div class="field"><label>Note (shown on the invoice)</label><textarea v-model="inv.note" class="textarea"></textarea></div>
                    <div class="field"><label>Payment record</label><textarea v-model="inv.payment_record" class="textarea"></textarea></div>
                </div>
                <div>
                    <div class="lbl">This invoice will show</div>
                    <div class="card">
                        <table class="t">
                            <tbody>
                                <tr v-for="l in inv.snap.lines" :key="l.no"><td>{{ l.name }}</td><td class="num" :class="{ red: l.pending }">{{ l.amount_text }}</td></tr>
                                <tr><td class="muted">Subtotal</td><td class="num strong">{{ rs(inv.snap.subtotal) }}</td></tr>
                                <tr v-for="(x, k) in inv.snap.payments" :key="k"><td class="muted">{{ x.label }}</td><td class="num green">− {{ fmt(x.amount) }}</td></tr>
                            </tbody>
                            <tfoot><tr><td>Balance due</td><td class="num">{{ rs(inv.snap.balance) }}</td></tr></tfoot>
                        </table>
                    </div>
                    <p v-if="inv.snap.has_pending" class="hint">{{ inv.snap.pending_sub }} will be added on delivery.</p>
                </div>
            </form>
            <template #footer><button class="btn" @click="inv = null">Cancel</button><button class="btn btn-primary" form="inv-form" :disabled="busy">Create invoice</button></template>
        </Modal>

        <Modal v-if="closing" title="Close project" @close="closing = false">
            <div v-if="s.blockers.length" class="alert red"><Icon name="alert" /><div>This project cannot be closed yet:<ul><li v-for="b in s.blockers" :key="b">{{ b }}</li></ul></div></div>
            <template v-else>
                <p>Everything is paid. Closing locks the project and creates each partner's share of the profit as <b>unpaid</b>.</p>
                <div class="card mt"><table class="t"><thead><tr><th>Who</th><th class="num">Share</th><th class="num">Amount</th></tr></thead><tbody>
                    <tr v-for="x in d.split_preview" :key="x.label"><td><b>{{ x.label }}</b></td><td class="num">{{ x.percent }}%</td><td class="num strong">{{ rs(x.amount) }}</td></tr>
                </tbody><tfoot><tr><td colspan="2">Profit ({{ rs(s.billed) }} − {{ rs(s.expenses) }})</td><td class="num">{{ rs(s.profit) }}</td></tr></tfoot></table></div>
            </template>
            <template #footer><button class="btn" @click="closing = false">Cancel</button><button class="btn btn-dark" :disabled="busy || s.blockers.length" @click="closeProject"><Icon name="lock" :size="15" /> Close project</button></template>
        </Modal>
    </template>
</template>
