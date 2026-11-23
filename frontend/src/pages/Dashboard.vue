<script setup>
import { computed, onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import BarChart from '../components/BarChart.vue';
import LineChart from '../components/LineChart.vue';
import Donut from '../components/Donut.vue';
import { api } from '../api';
import { state } from '../store';
import { PALETTE, date, kindLabel, daysText, fmt, rs, stateTone } from '../utils';

const d = ref(null);
onMounted(async () => (d.value = (await api.get('/dashboard')).data));

const labels = computed(() => d.value.months.map((m) => m.label));
const series = computed(() => [
    { name: 'Received', color: '#141414', values: d.value.months.map((m) => m.income) },
    { name: 'Expenses', color: '#e3161e', values: d.value.months.map((m) => m.expenses) },
]);
const donut = computed(() => d.value.by_kind.map((k, i) => ({ label: kindLabel(k.kind), value: k.total, color: PALETTE[i % PALETTE.length] })));
const delta = computed(() => {
    const { received_month: a, received_prev: b } = d.value.kpi;
    if (!b) return a ? 'First payments this month' : 'No payments yet';
    const p = Math.round(((a - b) / b) * 100);

    return `${p >= 0 ? '+' : ''}${p}% vs last month`;
});
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <div class="page-head">
        <div><h1>Dashboard</h1><p class="sub">{{ today }}</p></div>
        <div class="row"><router-link to="/projects" class="btn btn-primary"><Icon name="plus" :size="16" /> New project</router-link></div>
    </div>

    <div v-if="!d" class="empty">Loading…</div>
    <template v-else>
        <div class="kpis">
            <div class="card kpi"><div class="l"><Icon name="coins" :size="15" /> Received this month</div><div class="v">{{ rs(d.kpi.received_month) }}</div><div class="s">{{ delta }}</div></div>
            <div class="card kpi"><div class="l"><Icon name="alert" :size="15" /> Outstanding balance</div><div class="v">{{ rs(d.kpi.outstanding) }}</div><div class="s">{{ d.kpi.active_projects }} active projects</div></div>
            <div class="card kpi"><div class="l"><Icon name="briefcase" :size="15" /> Expected profit (open)</div><div class="v">{{ rs(d.kpi.profit_expected) }}</div><div class="s">Quoted minus expenses</div></div>
            <div class="card kpi"><div class="l"><Icon name="check" :size="15" /> Profit earned (closed)</div><div class="v">{{ rs(d.kpi.profit_realized) }}</div><div class="s">{{ d.kpi.closed_projects }} closed projects</div></div>
            <div class="card kpi dark"><div class="l"><Icon name="wallet" :size="15" /> Company account</div><div class="v">{{ rs(d.kpi.company_balance) }}</div><div class="s">Owes partners {{ rs(d.kpi.owed_partners) }}</div></div>
        </div>

        <div class="grid g-2-1 mt">
            <div class="card">
                <div class="card-head"><h3>Money in vs money out · last 12 months</h3><div class="legend"><span v-for="s in series" :key="s.name"><i :style="{ background: s.color }"></i>{{ s.name }}</span></div></div>
                <div class="card-pad"><BarChart :labels="labels" :series="series" /></div>
            </div>
            <div class="card">
                <div class="card-head"><h3>What we sell</h3></div>
                <div class="card-pad"><Donut v-if="donut.length" :items="donut" /><div v-else class="empty">No items quoted yet</div></div>
            </div>
        </div>

        <div class="grid g2 mt">
            <div class="card">
                <div class="card-head"><h3>Profit from closed projects</h3></div>
                <div class="card-pad"><LineChart :labels="labels" :values="d.months.map((m) => m.profit)" /></div>
            </div>
            <div class="card">
                <div class="card-head"><h3>Renewals needing attention</h3><router-link to="/renewals" class="btn btn-sm btn-ghost">View all</router-link></div>
                <div v-if="!d.renewals.length" class="empty"><b>All clear</b>No domains or hosting expire within {{ state.settings.renewal_alert_days || 30 }} days.</div>
                <div v-else class="table-wrap" style="max-height: 292px">
                    <table class="t"><tbody>
                        <tr v-for="r in d.renewals" :key="r.id" class="click" @click="$router.push(`/clients/${r.client_id}`)">
                            <td><b>{{ r.name }}</b><div class="sub">{{ r.client.name }} · {{ r.kind }}</div></td>
                            <td class="num"><Badge :tone="stateTone(r.state)">{{ daysText(r.days_left) }}</Badge><div class="sub">{{ date(r.expires_on) }}</div></td>
                        </tr>
                    </tbody></table>
                </div>
            </div>
        </div>

        <div class="grid g2 mt">
            <div class="card">
                <div class="card-head"><h3>Clients who still owe us</h3></div>
                <div v-if="!d.top_balances.length" class="empty"><b>Nothing outstanding</b>Every active project is paid up.</div>
                <div v-else class="table-wrap">
                    <table class="t"><tbody>
                        <tr v-for="p in d.top_balances" :key="p.id" class="click" @click="$router.push(`/projects/${p.id}`)">
                            <td><b>{{ p.client }}</b><div class="sub">{{ p.title }}</div></td>
                            <td style="width: 28%"><div class="bar"><i :style="{ width: Math.min(100, (p.paid / p.billed) * 100 || 0) + '%' }"></i></div><div class="sub">{{ Math.round((p.paid / p.billed) * 100) || 0 }}% paid</div></td>
                            <td class="num strong">{{ rs(p.balance) }}</td>
                        </tr>
                    </tbody></table>
                </div>
            </div>
            <div class="card">
                <div class="card-head"><h3>Recent payments</h3></div>
                <div v-if="!d.recent_payments.length" class="empty"><b>No payments yet</b>Record a payment from a project.</div>
                <div v-else class="table-wrap">
                    <table class="t"><tbody>
                        <tr v-for="p in d.recent_payments" :key="p.id" class="click" @click="$router.push(`/projects/${p.project_id}`)">
                            <td><b>{{ p.client }}</b><div class="sub">{{ p.project }}</div></td>
                            <td class="sub">{{ date(p.paid_at) }}</td>
                            <td class="num strong green">+ {{ fmt(p.amount) }}</td>
                        </tr>
                    </tbody></table>
                </div>
            </div>
        </div>
    </template>
</template>
