<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Icon from '../components/Icon.vue';
import Badge from '../components/Badge.vue';
import { api } from '../api';
import { date, rs } from '../utils';

const router = useRouter();
const list = ref(null);
const q = ref('');
const status = ref('active');
onMounted(async () => (list.value = (await api.get('/projects')).data));

const shown = computed(() => (list.value || []).filter((p) => (status.value === 'all' || p.status === status.value) && (!q.value || `${p.title} ${p.client.name}`.toLowerCase().includes(q.value.toLowerCase()))));
const count = (s) => (list.value || []).filter((p) => p.status === s).length;
</script>

<template>
    <div class="page-head">
        <div><h1>Projects</h1><p class="sub">Quotes, payments and profit per client</p></div>
        <button class="btn btn-primary" @click="router.push('/projects/new')"><Icon name="plus" :size="16" /> New project</button>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="chips">
                <button class="chip" :class="{ on: status === 'active' }" @click="status = 'active'">Active · {{ count('active') }}</button>
                <button class="chip" :class="{ on: status === 'closed' }" @click="status = 'closed'">Closed · {{ count('closed') }}</button>
                <button class="chip" :class="{ on: status === 'all' }" @click="status = 'all'">All</button>
            </div>
            <div class="search" style="width: 280px"><Icon name="search" :size="16" /><input v-model="q" class="input" placeholder="Search projects or clients…" /></div>
        </div>
        <div v-if="!list" class="empty">Loading…</div>
        <div v-else-if="!shown.length" class="empty"><b>No projects here</b>Create a project to send the first quotation.</div>
        <div v-else class="table-wrap">
            <table class="t">
                <thead><tr><th>Project</th><th>Status</th><th class="num">Billed</th><th class="num">Paid</th><th class="num">Balance</th><th class="num">Profit</th></tr></thead>
                <tbody>
                    <tr v-for="p in shown" :key="p.id" class="click" @click="router.push(`/projects/${p.id}`)">
                        <td><b>{{ p.client.name }}</b><div class="sub">{{ p.title }} · {{ p.type === 'monthly' ? 'Monthly' : 'One-time' }} · {{ date(p.start_date) }}</div></td>
                        <td><Badge :tone="p.status === 'closed' ? 'dark' : 'green'">{{ p.status === 'closed' ? 'Closed' : 'Active' }}</Badge></td>
                        <td class="num">{{ rs(p.billed) }}</td>
                        <td class="num green">{{ rs(p.paid) }}</td>
                        <td class="num strong" :class="{ red: p.balance > 0 }">{{ rs(p.balance) }}</td>
                        <td class="num">{{ rs(p.status === 'closed' ? p.final_profit : (p.billed || 0) - (p.expenses_total || 0)) }}<div v-if="p.status !== 'closed'" class="sub">expected</div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
