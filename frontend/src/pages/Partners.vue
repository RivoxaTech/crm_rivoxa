<script setup>
import { onMounted, ref } from 'vue';
import Icon from '../components/Icon.vue';
import { api } from '../api';
import { rs } from '../utils';

const list = ref(null);
onMounted(async () => (list.value = (await api.get('/partners')).data));
</script>

<template>
    <div class="page-head"><div><h1>Partners</h1><p class="sub">Profit shares, payouts and reimbursements</p></div></div>
    <div v-if="!list" class="empty">Loading…</div>
    <div v-else class="grid g2">
        <router-link v-for="p in list" :key="p.id" :to="`/partners/${p.id}`" class="card card-pad" style="display: block">
            <div class="row spread"><h3 style="font-size: 18px">{{ p.name }}</h3><Icon name="pie" /></div>
            <div class="kpis mt" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px">
                <div><div class="muted small strong">Earned</div><div class="strong" style="font-size: 17px">{{ rs(p.earned) }}</div></div>
                <div><div class="muted small strong">Paid out</div><div class="strong green" style="font-size: 17px">{{ rs(p.paid) }}</div></div>
                <div><div class="muted small strong">Unpaid</div><div class="strong red" style="font-size: 17px">{{ rs(p.unpaid) }}</div></div>
            </div>
            <p v-if="p.owed_expenses" class="small mt red strong">+ {{ rs(p.owed_expenses) }} expenses to reimburse</p>
        </router-link>
    </div>
</template>
