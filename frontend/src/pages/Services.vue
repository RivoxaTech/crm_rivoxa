<script setup>
import { onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import Modal from '../components/Modal.vue';
import ItemFields from '../components/ItemFields.vue';
import { api, errMsg } from '../api';
import { askConfirm, loadCatalog, toast } from '../store';
import { blankItem, rateOf, rs } from '../utils';

const list = ref(null);
const f = ref(null);
const load = async () => (list.value = await loadCatalog(true));
onMounted(load);

const open = (c) => (f.value = c ? { ...blankItem(), ...c, description: c.description || '', short_label: c.short_label || '', rate_text: c.rate_text || '', unit: c.unit || '', unit_price: c.unit_price ?? '', cost: c.cost ?? '' } : blankItem());
async function save() {
    const opt = (v) => (v === '' || v === null ? null : Number(v));
    const body = { ...f.value, unit_price: opt(f.value.unit_price), cost: opt(f.value.cost) };
    try {
        f.value.id ? await api.put(`/catalog/${f.value.id}`, body) : await api.post('/catalog', body);
        f.value = null;
        toast('Saved');
        load();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}
async function remove(c) {
    if (await askConfirm({ title: 'Delete service', message: `Delete "${c.name}"? Existing projects are not affected.`, ok: 'Delete', danger: true })) {
        await api.delete(`/catalog/${c.id}`);
        load();
    }
}
</script>

<template>
    <div class="page-head">
        <div><h1>Services &amp; prices</h1><p class="sub">Everything Rivoxa offers. Prices are optional here: you set the price and cost per client when you quote a project.</p></div>
        <button class="btn btn-primary" @click="open(null)"><Icon name="plus" :size="16" /> New service</button>
    </div>
    <div class="card">
        <div v-if="!list" class="empty">Loading…</div>
        <div v-else-if="!list.length" class="empty"><b>No services yet</b>Add the services you offer.</div>
        <div v-else class="table-wrap">
            <table class="t">
                <thead><tr><th>Service</th><th class="num">Price</th><th class="num">Our cost</th><th class="num">Margin</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="c in list" :key="c.id">
                        <td><b>{{ c.name }}</b><div class="sub">{{ c.description }}</div></td>
                        <td class="num">{{ rateOf(c) }}</td>
                        <td class="num muted">{{ c.cost != null ? rs(c.cost) : '—' }}</td>
                        <td class="num green">{{ c.billing === 'fixed' && c.unit_price != null && c.cost != null ? rs(c.unit_price - c.cost) : '—' }}</td>
                        <td class="actions"><button class="btn btn-sm btn-icon" @click="open(c)"><Icon name="edit" :size="14" /></button> <button class="btn btn-sm btn-icon btn-danger" @click="remove(c)"><Icon name="trash" :size="14" /></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <Modal v-if="f" :title="f.id ? 'Edit service' : 'New service'" @close="f = null">
        <form id="svc-form" @submit.prevent="save"><ItemFields v-model="f" catalog /></form>
        <template #footer><button class="btn" @click="f = null">Cancel</button><button class="btn btn-primary" form="svc-form">Save service</button></template>
    </Modal>
</template>
