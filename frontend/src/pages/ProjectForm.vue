<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import ItemFields from '../components/ItemFields.vue';
import ClientForm from '../components/ClientForm.vue';
import { api, errMsg } from '../api';
import { loadCatalog, state, toast } from '../store';
import { blankItem, calcAmount, fromCatalog, rs, toPayload, today } from '../utils';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const editing = !!id;
const line = () => ({ name: '', description: '', unit_price: '', cost: '' });

const f = reactive({
    client_id: route.query.client ? Number(route.query.client) : '', title: '', description: '', type: 'one_time', split_mode: 'half',
    start_date: today(), notes: '', auto_generate: false, billing_day: 1, recurring: [line()],
});
const items = ref([]);
const clients = ref([]);
const pick = ref('');
const newClient = ref(false);
const busy = ref(false);
const ready = ref(false);

onMounted(async () => {
    const [c] = await Promise.all([api.get('/clients'), loadCatalog()]);
    clients.value = c.data;
    if (editing) {
        const p = (await api.get(`/projects/${id}`)).data.project;
        Object.assign(f, p, { recurring: p.recurring?.length ? p.recurring : [line()] });
    }
    ready.value = true;
});

function addFromCatalog() {
    const c = state.catalog.find((x) => x.id === pick.value);
    items.value.push(c ? fromCatalog(c) : blankItem());
    pick.value = '';
}

/** Live profit preview so the quote can be judged before saving. */
const preview = computed(() => {
    const lines = f.type === 'monthly' ? f.recurring.filter((r) => r.name) : items.value;
    const billed = lines.reduce((a, l) => a + (f.type === 'monthly' ? Number(l.unit_price) || 0 : calcAmount(l) || 0), 0);
    const cost = lines.reduce((a, l) => a + (Number(l.cost) || 0), 0);
    const profit = billed - cost;
    const [a, b] = state.partners.length ? [state.partners[0].name, state.partners[1]?.name || 'Partner 2'] : ['Partner 1', 'Partner 2'];
    const pct = f.split_mode === 'half' ? [25, 25, 50] : [100 / 3, 100 / 3, 100 / 3];
    const shares = [a, b, 'Company'].map((name, i) => ({ name, percent: Math.round(pct[i] * 10) / 10, amount: Math.round((profit * pct[i]) / 100) }));

    return { billed, cost, profit, shares };
});

async function save() {
    if (!f.client_id) return toast('Choose a client first', 'error');
    busy.value = true;
    try {
        const body = { ...f, recurring: f.type === 'monthly' ? f.recurring.filter((r) => r.name).map((r) => ({ ...r, unit_price: Number(r.unit_price) || 0, cost: Number(r.cost) || 0 })) : null };
        if (!editing) body.items = items.value.map(toPayload);
        const { data } = editing ? await api.put(`/projects/${id}`, body) : await api.post('/projects', body);
        toast(editing ? 'Project updated' : 'Project created');
        router.push(`/projects/${data.project.id}`);
    } catch (e) {
        toast(errMsg(e), 'error');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <router-link :to="editing ? `/projects/${id}` : '/projects'" class="back"><Icon name="back" :size="16" /> {{ editing ? 'Back to project' : 'Projects' }}</router-link>
    <div class="page-head">
        <div><h1>{{ editing ? 'Edit project' : 'New project' }}</h1><p class="sub">{{ editing ? 'Change the details. Items are edited inside the project.' : 'Choose the client, add what you are quoting, then set the price and our cost for each item.' }}</p></div>
    </div>

    <div v-if="!ready" class="empty">Loading…</div>
    <form v-else class="stack" style="gap: 18px; max-width: 980px" @submit.prevent="save">
        <div class="card card-pad">
            <div class="form-grid">
                <div class="field">
                    <label>Client</label>
                    <div class="row">
                        <select v-model="f.client_id" class="select" required :disabled="editing"><option value="" disabled>Select client</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                        <button v-if="!editing" type="button" class="btn btn-icon" title="New client" @click="newClient = true"><Icon name="plus" :size="16" /></button>
                    </div>
                </div>
                <div class="field"><label>Project title</label><input v-model="f.title" class="input" required placeholder="e.g. RS Home website" /></div>
                <div class="field span-2"><label>Description on invoice (under "Billed to")</label><input v-model="f.description" class="input" placeholder="Website development & hosting services" /></div>
                <div class="field"><label>Project type</label>
                    <select v-model="f.type" class="select" :disabled="editing"><option value="one_time">One-time project</option><option value="monthly">Monthly (e.g. ads management)</option></select>
                </div>
                <div class="field"><label>Start date</label><input v-model="f.start_date" type="date" class="input" /></div>
                <div class="field span-2">
                    <label>Profit split when the project closes</label>
                    <div class="choice">
                        <label :class="{ on: f.split_mode === 'half' }"><input v-model="f.split_mode" type="radio" value="half" /><b>50% partners · 50% company</b><span>Each partner 25%, company 50%</span></label>
                        <label :class="{ on: f.split_mode === 'equal' }"><input v-model="f.split_mode" type="radio" value="equal" /><b>Equal three ways</b><span>Each partner and the company get one third</span></label>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="f.type === 'monthly'" class="card card-pad">
            <div class="lbl">Monthly lines (billed every month)</div>
            <div v-for="(r, i) in f.recurring" :key="i" class="itemcard form-grid" style="grid-template-columns: 2fr 1fr 1fr auto">
                <div class="field"><label>Name</label><input v-model="r.name" class="input" placeholder="Ads management" /></div>
                <div class="field"><label>Price (Rs)</label><input v-model="r.unit_price" type="number" min="0" step="any" class="input" :required="!!r.name" /></div>
                <div class="field"><label>Our cost (Rs)</label><input v-model="r.cost" type="number" min="0" step="any" class="input" :required="!!r.name" placeholder="0" /></div>
                <button type="button" class="btn btn-icon btn-danger" style="align-self: end" @click="f.recurring.splice(i, 1)"><Icon name="trash" :size="15" /></button>
            </div>
            <div class="row mt">
                <button type="button" class="btn btn-sm" @click="f.recurring.push(line())"><Icon name="plus" :size="14" /> Add line</button>
                <label class="check" style="margin-left: 14px"><input v-model="f.auto_generate" type="checkbox" /> Create the invoice automatically</label>
                <span v-if="f.auto_generate" class="row">on day <input v-model="f.billing_day" type="number" min="1" max="28" class="input" style="width: 70px" /> of each month</span>
            </div>
        </div>

        <div v-else-if="!editing" class="card card-pad">
            <div class="row spread wrap" style="margin-bottom: 12px; gap: 10px">
                <div class="lbl" style="margin: 0">Quoted items</div>
                <div class="row">
                    <select v-model="pick" class="select" style="width: 260px" @change="addFromCatalog"><option value="">+ Add a service…</option><option v-for="c in state.catalog" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                    <button type="button" class="btn" @click="items.push(blankItem())"><Icon name="plus" :size="15" /> Custom item</button>
                </div>
            </div>
            <div v-if="!items.length" class="empty" style="padding: 22px; border: 1px dashed var(--line-strong); border-radius: 10px">Add a service from the list. Then set its price and what it costs us.</div>
            <div v-for="(it, i) in items" :key="i" class="itemcard">
                <div class="row spread" style="margin-bottom: 10px"><b>Item {{ i + 1 }}</b><button type="button" class="btn btn-sm btn-ghost btn-danger" @click="items.splice(i, 1)"><Icon name="trash" :size="14" /> Remove</button></div>
                <ItemFields v-model="items[i]" />
            </div>
        </div>

        <div v-if="!editing" class="card card-pad">
            <div class="row spread wrap" style="gap: 14px">
                <div><div class="lbl" style="margin: 0">Expected profit</div><div class="muted small">Quoted {{ rs(preview.billed) }} − our costs {{ rs(preview.cost) }} = <b>{{ rs(preview.profit) }}</b></div></div>
                <div class="row wrap" style="gap: 22px">
                    <div v-for="x in preview.shares" :key="x.name"><div class="muted small">{{ x.name }} · {{ x.percent }}%</div><b>{{ rs(x.amount) }}</b></div>
                </div>
            </div>
        </div>

        <div class="card card-pad"><div class="field"><label>Notes (private)</label><textarea v-model="f.notes" class="textarea" style="min-height: 56px"></textarea></div></div>

        <div class="row" style="justify-content: flex-end; gap: 10px">
            <button type="button" class="btn" @click="router.back()">Cancel</button>
            <button class="btn btn-primary" :disabled="busy">{{ editing ? 'Save changes' : 'Create project' }}</button>
        </div>
    </form>
    <ClientForm v-if="newClient" @close="newClient = false" @saved="(c) => { clients.push(c); f.client_id = c.id; }" />
</template>
