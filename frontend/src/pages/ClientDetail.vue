<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ClientForm from '../components/ClientForm.vue';
import { api, errMsg } from '../api';
import { askConfirm, toast } from '../store';
import { date, daysText, rs, stateTone } from '../utils';

const route = useRoute();
const router = useRouter();
const d = ref(null);
const edit = ref(false);
const svc = ref(null);
const load = async () => (d.value = (await api.get(`/clients/${route.params.id}`)).data);
onMounted(load);

const totals = computed(() => ({
    billed: d.value.projects.reduce((a, p) => a + (+p.billed || 0), 0),
    paid: d.value.projects.reduce((a, p) => a + (+p.paid || 0), 0),
    balance: d.value.projects.filter((p) => p.status === 'active').reduce((a, p) => a + p.balance, 0),
}));

async function remove() {
    if (!(await askConfirm({ title: 'Delete client', message: `Delete ${d.value.client.name}? This cannot be undone.`, ok: 'Delete', danger: true }))) return;
    try {
        await api.delete(`/clients/${d.value.client.id}`);
        router.push('/clients');
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}

const blank = () => ({ id: null, client_id: d.value.client.id, kind: 'domain', name: '', provider: '', purchased_on: '', expires_on: '', renewal_price: '', renewal_cost: '', notes: '' });
async function saveSvc() {
    const body = { ...svc.value, purchased_on: svc.value.purchased_on || null, expires_on: svc.value.expires_on || null, renewal_price: svc.value.renewal_price || null, renewal_cost: svc.value.renewal_cost || null };
    try {
        svc.value.id ? await api.put(`/renewals/${svc.value.id}`, body) : await api.post('/renewals', body);
        svc.value = null;
        toast('Saved');
        load();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
async function delSvc(s) {
    if (await askConfirm({ title: 'Remove record', message: `Remove ${s.name}?`, ok: 'Remove', danger: true })) {
        await api.delete(`/renewals/${s.id}`);
        load();
    }
}
</script>

<template>
    <div v-if="!d" class="empty">Loading…</div>
    <template v-else>
        <router-link to="/clients" class="back"><Icon name="back" :size="16" /> Clients</router-link>
        <div class="page-head">
            <div><h1>{{ d.client.name }}</h1><p class="sub">{{ d.client.contact_person || 'Client' }}</p></div>
            <div class="row">
                <button class="btn" @click="edit = true"><Icon name="edit" :size="15" /> Edit</button>
                <button class="btn btn-danger" @click="remove"><Icon name="trash" :size="15" /></button>
                <button class="btn btn-primary" @click="router.push(`/projects/new?client=${d.client.id}`)"><Icon name="plus" :size="16" /> New project</button>
            </div>
        </div>

        <div class="grid g-2-1">
            <div class="card">
                <div class="summary" style="grid-template-columns: repeat(3, minmax(0, 1fr))">
                    <div><div class="l">Total billed</div><div class="v">{{ rs(totals.billed) }}</div></div>
                    <div><div class="l">Paid so far</div><div class="v green">{{ rs(totals.paid) }}</div></div>
                    <div><div class="l">Balance (active)</div><div class="v" :class="{ red: totals.balance > 0 }">{{ rs(totals.balance) }}</div></div>
                </div>
            </div>
            <div class="card card-pad">
                <dl class="dl" style="margin: 0">
                    <dt>Phone</dt><dd>{{ d.client.phone || '—' }}</dd>
                    <dt>WhatsApp</dt><dd>{{ d.client.whatsapp || d.client.phone || '—' }}</dd>
                    <dt>Email</dt><dd>{{ d.client.email || '—' }}</dd>
                    <dt>Address</dt><dd>{{ d.client.address || '—' }}</dd>
                    <template v-if="d.client.notes"><dt>Notes</dt><dd>{{ d.client.notes }}</dd></template>
                </dl>
            </div>
        </div>

        <div class="card mt">
            <div class="card-head"><h3>Projects</h3></div>
            <div v-if="!d.projects.length" class="empty"><b>No projects yet</b>Create the first quotation for this client.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>Project</th><th>Status</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th></tr></thead>
                    <tbody>
                        <tr v-for="p in d.projects" :key="p.id" class="click" @click="router.push(`/projects/${p.id}`)">
                            <td><b>{{ p.title }}</b><div class="sub">{{ p.type === 'monthly' ? 'Monthly' : 'One-time' }} · {{ date(p.start_date) }}</div></td>
                            <td><Badge :tone="p.status === 'closed' ? 'dark' : 'green'">{{ p.status === 'closed' ? 'Closed' : 'Active' }}</Badge></td>
                            <td class="num">{{ rs(p.billed) }}</td><td class="num green">{{ rs(p.paid) }}</td><td class="num strong">{{ rs(p.balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt">
            <div class="card-head"><h3>Domain &amp; hosting</h3><button class="btn btn-sm" @click="svc = blank()"><Icon name="plus" :size="14" /> Add</button></div>
            <div v-if="!d.services.length" class="empty"><b>Nothing tracked</b>Add the domain and hosting dates so we never miss a renewal.</div>
            <div v-else class="table-wrap">
                <table class="t">
                    <thead><tr><th>Name</th><th>Provider</th><th>Purchased</th><th>Expires</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="s in d.services" :key="s.id">
                            <td><b>{{ s.name }}</b><div class="sub">{{ s.kind }}</div></td>
                            <td>{{ s.provider || '—' }}</td><td>{{ date(s.purchased_on) }}</td>
                            <td>{{ date(s.expires_on) }} <Badge :tone="stateTone(s.state)">{{ daysText(s.days_left) }}</Badge></td>
                            <td class="actions"><button class="btn btn-sm btn-icon" @click="svc = { ...s, purchased_on: s.purchased_on || '', expires_on: s.expires_on || '', renewal_price: s.renewal_price ?? '', renewal_cost: s.renewal_cost ?? '' }"><Icon name="edit" :size="14" /></button> <button class="btn btn-sm btn-icon btn-danger" @click="delSvc(s)"><Icon name="trash" :size="14" /></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ClientForm v-if="edit" :client="d.client" @close="edit = false" @saved="load" />
        <Modal v-if="svc" :title="svc.id ? 'Edit record' : 'Add domain / hosting'" @close="svc = null">
            <form id="svc-form" class="form-grid" @submit.prevent="saveSvc">
                <div class="field"><label>Type</label><select v-model="svc.kind" class="select"><option value="domain">Domain</option><option value="hosting">Hosting</option><option value="other">Other</option></select></div>
                <div class="field"><label>Name</label><input v-model="svc.name" class="input" placeholder="example.com" required /></div>
                <div class="field"><label>Provider</label><input v-model="svc.provider" class="input" /></div>
                <div class="field"><label>Purchased on</label><input v-model="svc.purchased_on" type="date" class="input" /></div>
                <div class="field"><label>Expires on</label><input v-model="svc.expires_on" type="date" class="input" /></div>
                <div class="field"><label>Renewal price we charge (Rs)</label><input v-model="svc.renewal_price" type="number" min="0" step="any" class="input" /></div>
                <div class="field"><label>Renewal cost to us (Rs)</label><input v-model="svc.renewal_cost" type="number" min="0" step="any" class="input" /></div>
                <div class="field span-2"><label>Notes</label><input v-model="svc.notes" class="input" /></div>
            </form>
            <template #footer><button class="btn" @click="svc = null">Cancel</button><button class="btn btn-primary" form="svc-form">Save</button></template>
        </Modal>
    </template>
</template>
