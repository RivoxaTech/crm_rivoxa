<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ExpenseForm from '../components/ExpenseForm.vue';
import { api, errMsg } from '../api';
import { askConfirm, toast } from '../store';
import { date, fmt, rs, today } from '../utils';

const d = ref(null);
const filter = ref('all');
const expense = ref(false);
const adj = ref(null);
const load = async () => (d.value = (await api.get('/account')).data);
onMounted(load);

const kinds = { client_payment: ['Client payment', 'green'], project_expense: ['Project cost', 'amber'], company_expense: ['Company expense', 'red'], reimbursement: ['Reimbursement', 'blue'], payout: ['Partner payout', 'dark'], adjustment: ['Adjustment', ''] };
const shown = computed(() => d.value.entries.filter((e) => filter.value === 'all' || (filter.value === 'in' ? e.type === 'in' : e.type === 'out')));

async function saveAdj() {
    try {
        await api.post('/account/adjustments', adj.value);
        adj.value = null;
        toast('Saved');
        load();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
async function remove(e) {
    const adjustment = e.id.startsWith('adj-');
    if (!(await askConfirm({ title: 'Delete entry', message: `Delete "${e.title}"?`, ok: 'Delete', danger: true }))) return;
    const id = e.id.split('-')[1];
    await api.delete(adjustment ? `/account/adjustments/${id}` : `/expenses/${id}`).catch((x) => toast(errMsg(x), 'error'));
    load();
}
</script>

<template>
    <div class="page-head">
        <div><h1>Company account</h1><p class="sub">Every rupee in and out of the company fund</p></div>
        <div class="row">
            <button class="btn" @click="adj = { type: 'deposit', amount: '', adjusted_on: today(), note: '' }"><Icon name="plus" :size="15" /> Add / take out money</button>
            <button class="btn btn-primary" @click="expense = true"><Icon name="plus" :size="15" /> Company expense</button>
        </div>
    </div>

    <div v-if="!d" class="empty">Loading…</div>
    <template v-else>
        <div class="kpis">
            <div class="card kpi dark"><div class="l"><Icon name="wallet" :size="15" /> Balance in account</div><div class="v">{{ rs(d.balance) }}</div><div class="s">Money received minus money spent</div></div>
            <div class="card kpi"><div class="l">Total received</div><div class="v green">{{ rs(d.total_in) }}</div></div>
            <div class="card kpi"><div class="l">Total spent</div><div class="v">{{ rs(d.total_out) }}</div></div>
            <div class="card kpi"><div class="l">Owed to partners</div><div class="v red">{{ rs(d.owed_partners) }}</div><div class="s">Unpaid profit shares + reimbursements</div></div>
            <div class="card kpi"><div class="l">Free after partners</div><div class="v">{{ rs(d.balance - d.owed_partners) }}</div><div class="s">What really belongs to the company</div></div>
        </div>

        <div class="card mt-lg">
            <div class="card-head">
                <h3>Transactions</h3>
                <div class="chips"><button v-for="(l, k) in { all: 'All', in: 'Money in', out: 'Money out' }" :key="k" class="chip" :class="{ on: filter === k }" @click="filter = k">{{ l }}</button></div>
            </div>
            <div v-if="!shown.length" class="empty"><b>No transactions</b>Client payments and expenses will appear here.</div>
            <div v-else class="table-wrap" style="max-height: 640px">
                <table class="t">
                    <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="num">In</th><th class="num">Out</th><th class="num">Balance</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="e in shown" :key="e.id">
                            <td>{{ date(e.date) }}</td>
                            <td><b>{{ e.title }}</b><div class="sub">{{ e.detail }}</div></td>
                            <td><Badge :tone="kinds[e.kind][1]">{{ kinds[e.kind][0] }}</Badge></td>
                            <td class="num green">{{ e.type === 'in' ? '+ ' + fmt(e.amount) : '' }}</td>
                            <td class="num">{{ e.type === 'out' ? '− ' + fmt(e.amount) : '' }}</td>
                            <td class="num strong">{{ fmt(e.balance) }}</td>
                            <td class="actions"><button v-if="e.id.startsWith('adj-') || e.kind === 'company_expense'" class="btn btn-sm btn-icon btn-danger" @click="remove(e)"><Icon name="trash" :size="14" /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ExpenseForm v-if="expense" @close="expense = false" @saved="load" />
        <Modal v-if="adj" title="Add or take out money" @close="adj = null">
            <form id="adj-form" class="form-grid" @submit.prevent="saveAdj">
                <div class="field span-2"><label>What happened?</label>
                    <select v-model="adj.type" class="select"><option value="deposit">Money added to the company account (opening balance, top-up)</option><option value="withdrawal">Money taken out (not a client payout)</option></select>
                </div>
                <div class="field"><label>Amount (Rs)</label><input v-model="adj.amount" type="number" min="0" step="any" class="input" required /></div>
                <div class="field"><label>Date</label><input v-model="adj.adjusted_on" type="date" class="input" required /></div>
                <div class="field span-2"><label>Reason</label><input v-model="adj.note" class="input" required placeholder="e.g. Opening balance" /></div>
            </form>
            <template #footer><button class="btn" @click="adj = null">Cancel</button><button class="btn btn-primary" form="adj-form">Save</button></template>
        </Modal>
    </template>
</template>
