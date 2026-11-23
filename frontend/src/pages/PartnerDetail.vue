<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import { api, errMsg } from '../api';
import { askConfirm, toast } from '../store';
import { METHODS, date, rs, today } from '../utils';

const route = useRoute();
const d = ref(null);
const pay = ref(null);
const load = async () => (d.value = (await api.get(`/partners/${route.params.id}`)).data);
onMounted(load);

const openPay = (share) => (pay.value = { share, amount: share ? share.remaining : d.value.partner.unpaid, paid_on: today(), method: 'bank', note: '' });
async function savePay() {
    const { share, ...body } = pay.value;
    try {
        share ? await api.post(`/shares/${share.id}/payouts`, body) : await api.post(`/partners/${d.value.partner.id}/pay-all`, body);
        pay.value = null;
        toast('Payout recorded from the company account');
        load();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
async function delPayout(o) {
    if (await askConfirm({ title: 'Delete payout', message: `Delete the ${rs(o.amount)} payout? The share becomes unpaid again.`, ok: 'Delete', danger: true })) {
        await api.delete(`/payouts/${o.id}`);
        load();
    }
}
async function reimburse(e) {
    if (await askConfirm({ title: 'Reimburse', message: `Pay ${rs(e.amount)} back from the company account?`, ok: 'Reimburse' })) {
        await api.post(`/expenses/${e.id}/reimburse`);
        toast('Reimbursed');
        load();
    }
}
const status = (x) => (x.paid_amount <= 0 ? ['Unpaid', 'amber'] : x.remaining <= 0 ? ['Paid', 'green'] : ['Part paid', 'blue']);
</script>

<template>
    <div v-if="!d" class="empty">Loading…</div>
    <template v-else>
        <router-link to="/partners" class="back"><Icon name="back" :size="16" /> Partners</router-link>
        <div class="page-head">
            <div><h1>{{ d.partner.name }}</h1></div>
            <button class="btn btn-primary" :disabled="d.partner.unpaid <= 0" @click="openPay(null)"><Icon name="send" :size="15" /> Pay all unpaid</button>
        </div>

        <div class="kpis">
            <div class="card kpi"><div class="l">Total earned</div><div class="v">{{ rs(d.partner.earned) }}</div></div>
            <div class="card kpi"><div class="l">Paid out</div><div class="v green">{{ rs(d.partner.paid) }}</div></div>
            <div class="card kpi"><div class="l">Unpaid share</div><div class="v red">{{ rs(d.partner.unpaid) }}</div></div>
            <div class="card kpi"><div class="l">Expenses to reimburse</div><div class="v">{{ rs(d.partner.owed_expenses) }}</div></div>
        </div>

        <div v-if="d.owed_expenses.length" class="card mt-lg">
            <div class="card-head"><h3>Expenses paid from pocket, waiting for reimbursement</h3></div>
            <table class="t"><tbody>
                <tr v-for="e in d.owed_expenses" :key="e.id">
                    <td>{{ date(e.spent_on) }}</td><td><b>{{ e.title }}</b><div class="sub">{{ e.reason }}{{ e.project ? ' · ' + e.project.title : '' }}</div></td>
                    <td class="num strong">{{ rs(e.amount) }}</td><td class="actions"><button class="btn btn-sm" @click="reimburse(e)">Reimburse</button></td>
                </tr>
            </tbody></table>
        </div>

        <div class="card mt-lg">
            <div class="card-head"><h3>Profit shares</h3></div>
            <div v-if="!d.shares.length" class="empty"><b>No shares yet</b>Shares appear when a project is closed.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>Project</th><th class="num">Share</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Remaining</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="x in d.shares" :key="x.id">
                            <td><router-link :to="`/projects/${x.project_id}`"><b>{{ x.project.client.name }}</b><div class="sub">{{ x.project.title }} · closed {{ date(x.project.closed_at) }}</div></router-link></td>
                            <td class="num">{{ x.percent }}%</td><td class="num strong">{{ rs(x.amount) }}</td><td class="num green">{{ rs(x.paid_amount) }}</td><td class="num">{{ rs(x.remaining) }}</td>
                            <td><Badge :tone="status(x)[1]">{{ status(x)[0] }}</Badge></td>
                            <td class="actions"><button v-if="x.remaining > 0" class="btn btn-sm" @click="openPay(x)">Mark as paid</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-lg">
            <div class="card-head"><h3>Payout history</h3></div>
            <div v-if="!d.payouts.length" class="empty">No payouts yet.</div>
            <div v-else class="table-wrap">
                <table class="t"><tbody>
                    <tr v-for="o in d.payouts" :key="o.id">
                        <td>{{ date(o.paid_on) }}</td><td><b>{{ o.share?.project?.title }}</b><div class="sub">{{ METHODS[o.method] }}{{ o.note ? ' · ' + o.note : '' }}</div></td>
                        <td class="num strong">{{ rs(o.amount) }}</td>
                        <td class="actions"><a class="btn btn-sm" :href="`/api/payouts/${o.id}/voucher`" target="_blank"><Icon name="file" :size="14" /> Voucher</a> <button class="btn btn-sm btn-icon btn-danger" @click="delPayout(o)"><Icon name="trash" :size="14" /></button></td>
                    </tr>
                </tbody></table>
            </div>
        </div>

        <Modal v-if="pay" :title="pay.share ? `Pay ${d.partner.name}: ${pay.share.project.title}` : `Pay ${d.partner.name}`" @close="pay = null">
            <form id="pay-form" class="form-grid" @submit.prevent="savePay">
                <div class="field"><label>Amount (Rs)</label><input v-model="pay.amount" type="number" min="0" step="any" class="input" required /><p class="hint">Unpaid: {{ rs(pay.share ? pay.share.remaining : d.partner.unpaid) }}</p></div>
                <div class="field"><label>Date paid</label><input v-model="pay.paid_on" type="date" class="input" required /></div>
                <div class="field"><label>Method</label><select v-model="pay.method" class="select"><option v-for="(l, k) in METHODS" :key="k" :value="k">{{ l }}</option></select></div>
                <div class="field"><label>Note</label><input v-model="pay.note" class="input" placeholder="Optional" /></div>
                <p class="hint span-2">This is paid out of the company account and shows in its transactions.</p>
            </form>
            <template #footer><button class="btn" @click="pay = null">Cancel</button><button class="btn btn-primary" form="pay-form">Record payout</button></template>
        </Modal>
    </template>
</template>
