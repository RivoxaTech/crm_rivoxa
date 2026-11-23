<script setup>
import { onMounted, reactive, ref } from 'vue';
import { api, errMsg } from '../api';
import { refreshShared } from '../router';
import { state, toast } from '../store';

const s = reactive({});
const partners = ref([]);
const me = reactive({ name: '', email: '' });
const pw = reactive({ current: '', password: '', password_confirmation: '' });

onMounted(async () => {
    const { data } = await api.get('/settings');
    Object.assign(s, data.settings, { auto_email_recurring: data.settings.auto_email_recurring === '1' });
    partners.value = data.partners;
    Object.assign(me, { name: state.user.name, email: state.user.email });
});

const attempt = async (fn, ok) => {
    try {
        await fn();
        toast(ok);
        await refreshShared();
    } catch (e) {
        toast(errMsg(e), 'error');
    }
};
const saveCompany = () => attempt(() => api.put('/settings', s), 'Settings saved');
const savePartner = (p) => attempt(() => api.put(`/partners/${p.id}`, p), `${p.name} saved`);
const saveMe = () => attempt(async () => (state.user = (await api.put('/profile', me)).data), 'Login details saved');
const savePw = () => attempt(async () => {
    await api.put('/password', pw);
    Object.assign(pw, { current: '', password: '', password_confirmation: '' });
}, 'Password changed');
</script>

<template>
    <div class="page-head"><div><h1>Settings</h1><p class="sub">Company details, partners and your login</p></div></div>
    <div class="grid g2" style="align-items: start">
        <form class="card" @submit.prevent="saveCompany">
            <div class="card-head"><h3>Company &amp; invoices</h3></div>
            <div class="card-pad form-grid">
                <div class="field"><label>Company name</label><input v-model="s.company_name" class="input" required /></div>
                <div class="field"><label>Website (invoice footer)</label><input v-model="s.company_site" class="input" /></div>
                <div class="field"><label>Company email</label><input v-model="s.company_email" type="email" class="input" /></div>
                <div class="field"><label>Company phone</label><input v-model="s.company_phone" class="input" /></div>
                <div class="field"><label>Invoice number prefix</label><input v-model="s.invoice_prefix" class="input" required /><p class="hint">Numbers look like {{ s.invoice_prefix }}-{{ new Date().getFullYear() }}-001</p></div>
                <div class="field"><label>Phone country code (WhatsApp)</label><input v-model="s.country_code" class="input" required /><p class="hint">92 = Pakistan. Used when a number starts with 0.</p></div>
                <div class="field"><label>Warn about renewals this many days ahead</label><input v-model="s.renewal_alert_days" type="number" min="1" class="input" required /></div>
                <label class="check" style="align-self: center"><input v-model="s.auto_email_recurring" type="checkbox" /> Email monthly invoices automatically</label>
            </div>
            <div class="modal-foot"><button class="btn btn-primary">Save settings</button></div>
        </form>

        <div class="stack">
            <div class="card">
                <div class="card-head"><h3>Partners</h3></div>
                <div class="card-pad stack">
                    <form v-for="p in partners" :key="p.id" class="form-grid" style="grid-template-columns: 1fr 1fr auto; align-items: end" @submit.prevent="savePartner(p)">
                        <div class="field"><label>Name</label><input v-model="p.name" class="input" required /></div>
                        <div class="field"><label>Phone</label><input v-model="p.phone" class="input" /></div>
                        <button class="btn">Save</button>
                    </form>
                </div>
            </div>
            <form class="card" @submit.prevent="saveMe">
                <div class="card-head"><h3>Login</h3></div>
                <div class="card-pad form-grid">
                    <div class="field"><label>Name</label><input v-model="me.name" class="input" required /></div>
                    <div class="field"><label>Email</label><input v-model="me.email" type="email" class="input" required /></div>
                </div>
                <div class="modal-foot"><button class="btn">Save login</button></div>
            </form>
            <form class="card" @submit.prevent="savePw">
                <div class="card-head"><h3>Change password</h3></div>
                <div class="card-pad form-grid">
                    <div class="field span-2"><label>Current password</label><input v-model="pw.current" type="password" class="input" autocomplete="current-password" required /></div>
                    <div class="field"><label>New password</label><input v-model="pw.password" type="password" class="input" autocomplete="new-password" minlength="8" required /></div>
                    <div class="field"><label>Repeat new password</label><input v-model="pw.password_confirmation" type="password" class="input" autocomplete="new-password" required /></div>
                </div>
                <div class="modal-foot"><button class="btn">Change password</button></div>
            </form>
        </div>
    </div>
</template>
