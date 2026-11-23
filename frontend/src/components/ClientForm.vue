<script setup>
import { reactive, ref } from 'vue';
import Modal from './Modal.vue';
import { api, errMsg } from '../api';
import { toast } from '../store';

const props = defineProps({ client: Object });
const emit = defineEmits(['close', 'saved']);

const f = reactive({ name: '', contact_person: '', phone: '', whatsapp: '', email: '', address: '', notes: '', ...(props.client || {}) });
const busy = ref(false);

async function save() {
    busy.value = true;
    try {
        const { data } = props.client?.id ? await api.put(`/clients/${props.client.id}`, f) : await api.post('/clients', f);
        toast('Client saved');
        emit('saved', data);
        emit('close');
    } catch (e) {
        toast(errMsg(e), 'error');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <Modal :title="client?.id ? 'Edit client' : 'New client'" @close="emit('close')">
        <form id="client-form" class="form-grid" @submit.prevent="save">
            <div class="field span-2"><label>Client / business name</label><input v-model="f.name" class="input" required autofocus /></div>
            <div class="field"><label>Contact person</label><input v-model="f.contact_person" class="input" /></div>
            <div class="field"><label>Phone</label><input v-model="f.phone" class="input" placeholder="0300 1234567" /></div>
            <div class="field"><label>WhatsApp (if different)</label><input v-model="f.whatsapp" class="input" /></div>
            <div class="field"><label>Email</label><input v-model="f.email" type="email" class="input" /></div>
            <div class="field span-2"><label>Address</label><input v-model="f.address" class="input" /></div>
            <div class="field span-2"><label>Notes</label><textarea v-model="f.notes" class="textarea"></textarea></div>
        </form>
        <template #footer>
            <button class="btn" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" form="client-form" :disabled="busy">Save client</button>
        </template>
    </Modal>
</template>
