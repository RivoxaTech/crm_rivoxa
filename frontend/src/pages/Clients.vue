<script setup>
import { computed, onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import ClientForm from '../components/ClientForm.vue';
import { api } from '../api';
import { rs } from '../utils';

const list = ref(null);
const q = ref('');
const form = ref(false);
const load = async () => (list.value = (await api.get('/clients')).data);
onMounted(load);

const shown = computed(() => list.value.filter((c) => !q.value || `${c.name} ${c.contact_person} ${c.phone}`.toLowerCase().includes(q.value.toLowerCase())));
</script>

<template>
    <div class="page-head">
        <div><h1>Clients</h1><p class="sub">Everyone we work with</p></div>
        <button class="btn btn-primary" @click="form = true"><Icon name="plus" :size="16" /> New client</button>
    </div>

    <div class="card">
        <div class="card-head"><div class="search" style="width: 300px"><Icon name="search" :size="16" /><input v-model="q" class="input" placeholder="Search clients…" /></div><span class="muted small">{{ list?.length || 0 }} clients</span></div>
        <div v-if="!list" class="empty">Loading…</div>
        <div v-else-if="!shown.length" class="empty"><b>No clients found</b>Add your first client to start quoting.</div>
        <div v-else class="table-wrap">
            <table class="t">
                <thead><tr><th>Client</th><th>Phone</th><th class="num">Projects</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th></tr></thead>
                <tbody>
                    <tr v-for="c in shown" :key="c.id" class="click" @click="$router.push(`/clients/${c.id}`)">
                        <td><b>{{ c.name }}</b><div class="sub">{{ c.contact_person || c.email || '—' }}</div></td>
                        <td>{{ c.phone || '—' }}</td>
                        <td class="num">{{ c.projects }}</td>
                        <td class="num">{{ rs(c.billed) }}</td>
                        <td class="num green">{{ rs(c.paid) }}</td>
                        <td class="num strong" :class="{ red: c.balance > 0 }">{{ rs(c.balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <ClientForm v-if="form" @close="form = false" @saved="(c) => $router.push(`/clients/${c.id}`)" />
</template>
