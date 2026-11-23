<script setup>
import { ref } from 'vue';
import Icon from './Icon.vue';
import Modal from './Modal.vue';
import { api, errMsg, pdfUrl } from '../api';
import { toast } from '../store';

const props = defineProps({ invoice: Object, deletable: Boolean });
const emit = defineEmits(['changed']);

const preview = ref(false);
const mail = ref(null);
const busy = ref(false);

function printPdf() {
    const f = document.createElement('iframe');
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
    f.src = pdfUrl(props.invoice.id);
    f.onload = () => {
        try {
            f.contentWindow.focus();
            f.contentWindow.print();
        } catch {
            window.open(pdfUrl(props.invoice.id), '_blank');
        }
        setTimeout(() => f.remove(), 60000);
    };
    document.body.appendChild(f);
}

async function whatsapp() {
    const win = window.open('', '_blank'); // opened first so the browser does not block it
    try {
        const { data } = await api.get(`/invoices/${props.invoice.id}/share`);
        if (!data.phone) toast('No phone number on this client yet. Pick the contact in WhatsApp.', 'error');
        if (win) win.location = data.whatsapp_url;
        await api.post(`/invoices/${props.invoice.id}/whatsapp-sent`);
        emit('changed');
    } catch (e) {
        win?.close();
        toast(errMsg(e), 'error');
    }
}

async function openMail() {
    try {
        const { data } = await api.get(`/invoices/${props.invoice.id}/share`);
        mail.value = { to: data.email || '', message: '', client: data.client };
    } catch (e) {
        toast(errMsg(e), 'error');
    }
}

async function sendMail() {
    busy.value = true;
    try {
        await api.post(`/invoices/${props.invoice.id}/email`, { to: mail.value.to, message: mail.value.message || null });
        toast('Invoice emailed');
        mail.value = null;
        emit('changed');
    } catch (e) {
        toast(errMsg(e), 'error');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <span class="row" style="gap: 4px; justify-content: flex-end">
        <button class="btn btn-sm btn-icon" title="Preview" @click="preview = true"><Icon name="eye" :size="15" /></button>
        <a class="btn btn-sm btn-icon" title="Download PDF" :href="pdfUrl(invoice.id, true)"><Icon name="download" :size="15" /></a>
        <button class="btn btn-sm btn-icon" title="Print" @click="printPdf"><Icon name="printer" :size="15" /></button>
        <button class="btn btn-sm btn-icon" title="Send on WhatsApp" style="color: #1d6b3a" @click="whatsapp"><Icon name="chat" :size="15" /></button>
        <button class="btn btn-sm btn-icon" title="Send by email" @click="openMail"><Icon name="mail" :size="15" /></button>
        <slot />
    </span>

    <Modal v-if="preview" :title="`Invoice ${invoice.number}`" wide @close="preview = false">
        <iframe class="pdf-frame" :src="pdfUrl(invoice.id)"></iframe>
    </Modal>

    <Modal v-if="mail" :title="`Email invoice ${invoice.number}`" @close="mail = null">
        <form id="mail-form" class="stack" @submit.prevent="sendMail">
            <div class="field"><label>Send to</label><input v-model="mail.to" type="email" class="input" required placeholder="client@email.com" /></div>
            <div class="field"><label>Message (optional)</label><textarea v-model="mail.message" class="textarea" :placeholder="`Hello ${mail.client}, please find invoice ${invoice.number} attached.`"></textarea></div>
            <p class="hint">The invoice PDF is attached automatically.</p>
        </form>
        <template #footer>
            <button class="btn" @click="mail = null">Cancel</button>
            <button class="btn btn-primary" form="mail-form" :disabled="busy"><Icon name="send" :size="15" /> Send email</button>
        </template>
    </Modal>
</template>
