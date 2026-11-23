<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { api, errMsg } from '../api';
import { state } from '../store';
import { refreshShared } from '../router';

const logo = '/images/rivoxa-logo.png';
const router = useRouter();
const f = reactive({ email: '', password: '' });
const error = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        await api.get('/me').catch(() => {}); // makes sure the CSRF cookie exists
        state.user = (await api.post('/login', f)).data;
        await refreshShared();
        router.push('/');
    } catch (e) {
        error.value = errMsg(e);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="login">
        <div class="box">
            <img :src="logo" alt="Rivoxa" />
            <form class="card stack" @submit.prevent="submit">
                <div><h2 style="font-size: 18px">Sign in</h2><p class="muted small">Rivoxa client &amp; finance manager</p></div>
                <div class="field"><label>Email</label><input v-model="f.email" type="email" class="input" autocomplete="username" required autofocus /></div>
                <div class="field"><label>Password</label><input v-model="f.password" type="password" class="input" autocomplete="current-password" required /></div>
                <p v-if="error" class="err" style="margin: 0">{{ error }}</p>
                <button class="btn btn-primary" style="height: 42px" :disabled="busy">Sign in</button>
            </form>
        </div>
    </div>
</template>
