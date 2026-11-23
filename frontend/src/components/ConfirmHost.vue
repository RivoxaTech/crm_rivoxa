<script setup>
import { state } from '../store';
import Modal from './Modal.vue';

const done = (v) => {
    state.confirm.resolve(v);
    state.confirm = null;
};
</script>

<template>
    <Modal v-if="state.confirm" :title="state.confirm.title" @close="done(false)">
        <p>{{ state.confirm.message }}</p>
        <template #footer>
            <button class="btn" @click="done(false)">Cancel</button>
            <button class="btn" :class="state.confirm.danger ? 'btn-primary' : 'btn-dark'" @click="done(true)">{{ state.confirm.ok }}</button>
        </template>
    </Modal>
</template>
