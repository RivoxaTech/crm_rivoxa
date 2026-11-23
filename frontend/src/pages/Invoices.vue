<script setup>
import { onMounted, ref, watch } from 'vue';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import InvoiceActions from '../components/InvoiceActions.vue';
import { api } from '../api';
import { date, rs } from '../utils';

const list = ref(null);
const q = ref('');
const load = async () => (list.value = (await api.get('/invoices', { params: { q: q.value || undefined } })).data);
onMounted(load);
let t;
watch(q, () => {
    clearTimeout(t);
    t = setTimeout(load, 250);
});
</script>

<template>
    <div class="page-head"><div><h1>Invoices</h1><p class="sub">Every invoice we have sent. Create new ones from a project.</p></div></div>
    <div class="card">
        <div class="card-head"><div class="search" style="width: 300px"><Icon name="search" :size="16" /><input v-model="q" class="input" placeholder="Search number or client…" /></div></div>
        <div v-if="!list" class="empty">Loading…</div>
        <div v-else-if="!list.length" class="empty"><b>No invoices found</b>Open a project and press "Generate invoice".</div>
        <div v-else class="table-wrap">
            <table class="t">
                <thead><tr><th>Invoice</th><th>Client</th><th>Sent</th><th class="num">Total</th><th class="num">Balance</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="i in list" :key="i.id">
                        <td><b>{{ i.number }}</b><div class="sub">{{ date(i.issue_date) }}</div></td>
                        <td><router-link :to="`/projects/${i.project_id}`"><b>{{ i.project.client.name }}</b><div class="sub">{{ i.project.title }}</div></router-link></td>
                        <td><Badge v-if="i.whatsapp_at" tone="green">WhatsApp</Badge> <Badge v-if="i.emailed_at" tone="blue">Email</Badge><span v-if="!i.whatsapp_at && !i.emailed_at" class="faint">Not sent</span></td>
                        <td class="num">{{ rs(i.subtotal) }}</td>
                        <td class="num strong" :class="{ red: i.balance > 0 }">{{ rs(i.balance) }}</td>
                        <td class="actions"><InvoiceActions :invoice="i" @changed="load" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
