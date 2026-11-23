<script setup>
import { reactive, ref } from 'vue';
import Modal from './Modal.vue';
import { api, errMsg } from '../api';
import { state, toast } from '../store';
import { today } from '../utils';

const props = defineProps({ projectId: { type: Number, default: null }, expense: Object });
const emit = defineEmits(['close', 'saved']);

const f = reactive({
    project_id: props.projectId, title: '', reason: '', category: '', amount: '', spent_on: today(),
    paid_by: 'company', partner_id: state.partners[0]?.id ?? null, ...(props.expense || {}),
});
const busy = ref(false);

async function save() {
    busy.value = true;
    try {
        props.expense?.id ? await api.put(`/expenses/${props.expense.id}`, f) : await api.post('/expenses', f);
        toast('Expense saved');
        emit('saved');
        emit('close');
    } catch (e) {
        toast(errMsg(e), 'error');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <Modal :title="expense?.id ? 'Edit expense' : projectId ? 'Add project expense' : 'Add company expense'" @close="emit('close')">
        <form id="expense-form" class="form-grid" @submit.prevent="save">
            <div class="field span-2"><label>What was it?</label><input v-model="f.title" class="input" placeholder="e.g. Facebook ads, Petrol, Domain renewal" required autofocus /></div>
            <div class="field span-2"><label>Reason (required)</label><input v-model="f.reason" class="input" placeholder="Why was this spent?" required /></div>
            <div class="field"><label>Amount (Rs)</label><input v-model="f.amount" type="number" min="0" step="any" class="input" required /></div>
            <div class="field"><label>Date</label><input v-model="f.spent_on" type="date" class="input" required /></div>
            <div class="field"><label>Category</label>
                <input v-model="f.category" class="input" list="expense-cats" placeholder="Optional" />
                <datalist id="expense-cats"><option>ads</option><option>tools</option><option>fuel</option><option>domain</option><option>hosting</option><option>subscription</option><option>other</option></datalist>
            </div>
            <div class="field"><label>Paid from</label>
                <select v-model="f.paid_by" class="select"><option value="company">Company account</option><option value="partner">A partner's pocket</option></select>
            </div>
            <div v-if="f.paid_by === 'partner'" class="field span-2">
                <label>Which partner paid?</label>
                <select v-model="f.partner_id" class="select"><option v-for="p in state.partners" :key="p.id" :value="p.id">{{ p.name }}</option></select>
                <p class="hint">The company will reimburse this partner from the company account.</p>
            </div>
        </form>
        <template #footer>
            <button class="btn" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" form="expense-form" :disabled="busy">Save expense</button>
        </template>
    </Modal>
</template>
