<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import { api, errMsg } from '../api';
import { askConfirm, toast } from '../store';
import { date, daysText, rs, stateTone } from '../utils';

const router = useRouter();
const list = ref(null);
const filter = ref('all');
const renew = ref(null);
const load = async () => (list.value = (await api.get('/renewals')).data);
onMounted(load);

const shown = computed(() => list.value.filter((s) => filter.value === 'all' || (filter.value === 'attention' ? ['expired', 'soon'].includes(s.state) : s.state === filter.value)));
const openRenew = (s) => (renew.value = { s, years: 1, bill: !!s.renewal_price, price: s.renewal_price ?? '', cost: s.renewal_cost ?? '' });

async function doRenew() {
    const { s, ...body } = renew.value;
    try {
        const { data } = await api.post(`/renewals/${s.id}/renew`, { ...body, price: body.price || null, cost: body.cost || null });
        toast('Renewed');
        renew.value = null;
        data.project_id ? router.push(`/projects/${data.project_id}`) : load();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
async function remove(s) {
    if (await askConfirm({ title: 'Remove record', message: `Remove ${s.name}?`, ok: 'Remove', danger: true })) {
        await api.delete(`/renewals/${s.id}`);
        load();
    }
}
</script>

<template>
    <div class="page-head"><div><h1>Domains &amp; hosting</h1><p class="sub">Never miss a renewal. Add records from a client's page.</p></div></div>
    <div class="card">
        <div class="card-head"><div class="chips"><button v-for="(l, k) in { all: 'All', attention: 'Needs attention', expired: 'Expired', ok: 'Healthy' }" :key="k" class="chip" :class="{ on: filter === k }" @click="filter = k">{{ l }}</button></div></div>
        <div v-if="!list" class="empty">Loading…</div>
        <div v-else-if="!shown.length" class="empty"><b>Nothing here</b>Open a client and add their domain or hosting.</div>
        <div v-else class="table-wrap">
            <table class="t">
                <thead><tr><th>Name</th><th>Client</th><th>Provider</th><th>Purchased</th><th>Expires</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="s in shown" :key="s.id">
                        <td><b>{{ s.name }}</b><div class="sub">{{ s.kind }}</div></td>
                        <td><router-link :to="`/clients/${s.client_id}`"><b>{{ s.client.name }}</b></router-link></td>
                        <td>{{ s.provider || '—' }}</td><td>{{ date(s.purchased_on) }}</td>
                        <td>{{ date(s.expires_on) }}<div><Badge :tone="stateTone(s.state)">{{ daysText(s.days_left) }}</Badge></div></td>
                        <td class="actions"><button class="btn btn-sm" @click="openRenew(s)"><Icon name="refresh" :size="14" /> Renew</button> <button class="btn btn-sm btn-icon btn-danger" @click="remove(s)"><Icon name="trash" :size="14" /></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Modal v-if="renew" :title="`Renew ${renew.s.name}`" @close="renew = null">
        <form id="renew-form" class="form-grid" @submit.prevent="doRenew">
            <div class="field span-2"><label>Renew for</label><select v-model="renew.years" class="select"><option v-for="y in 5" :key="y" :value="y">{{ y }} year{{ y > 1 ? 's' : '' }}</option></select><p class="hint">Currently expires {{ date(renew.s.expires_on) }}</p></div>
            <label class="check span-2"><input v-model="renew.bill" type="checkbox" /> Also create a project and invoice for this renewal</label>
            <template v-if="renew.bill">
                <div class="field"><label>Price we charge (Rs)</label><input v-model="renew.price" type="number" min="0" step="any" class="input" required /></div>
                <div class="field"><label>Our cost (Rs)</label><input v-model="renew.cost" type="number" min="0" step="any" class="input" /></div>
            </template>
        </form>
        <template #footer><button class="btn" @click="renew = null">Cancel</button><button class="btn btn-primary" form="renew-form">Renew</button></template>
    </Modal>
</template>
