<script setup>
import { computed, onMounted } from 'vue';
import { loadCatalog, state } from '../store';
import { fmt, kindLabel, rs } from '../utils';

const f = defineModel({ required: true });
// catalog = editing the price list itself: no service picker, price and cost are optional
const props = defineProps({ catalog: Boolean });

onMounted(() => !props.catalog && loadCatalog());

const hint = computed(() => {
    if (f.value.is_free) return 'Shown as "Free" on the invoice.';
    if (f.value.amount !== '' && f.value.amount !== null) return `Total charged: ${rs(f.value.amount)}`;
    if (f.value.billing === 'fixed') return `Total charged: ${rs(f.value.unit_price || 0)}`;
    if (f.value.quantity === '' || f.value.quantity === null) return 'Quantity not known yet: invoice shows "On delivery" and the amount is added later.';

    return `${fmt(f.value.quantity)} × ${fmt(f.value.unit_price || 0)} = ${rs(f.value.quantity * (f.value.unit_price || 0))}`;
});

/** Picking a service fills the blanks (never overwrites what was typed). */
function pickService() {
    const c = (state.catalog || []).find((x) => x.name === f.value.kind);
    if (!c) return;
    const v = f.value;
    v.name ||= c.name;
    v.description ||= c.description || '';
    v.billing = c.billing;
    v.unit ||= c.unit || '';
    v.rate_text ||= c.rate_text || '';
    v.short_label ||= c.short_label || '';
    if (v.unit_price === '' && c.unit_price != null) v.unit_price = c.unit_price;
    if (v.cost === '' && c.cost != null) v.cost = c.cost;
}
</script>

<template>
    <div class="form-grid">
        <div class="field span-2"><label>Item name</label><input v-model="f.name" class="input" placeholder="e.g. Website Development" required /></div>
        <div v-if="!catalog" class="field"><label>Service</label>
            <select v-model="f.kind" class="select" @change="pickService">
                <option v-for="c in state.catalog || []" :key="c.id" :value="c.name">{{ c.name }}</option>
                <option v-if="f.kind && !(state.catalog || []).some((c) => c.name === f.kind) && f.kind !== 'Other'" :value="f.kind">{{ kindLabel(f.kind) }}</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="field"><label>Pricing</label>
            <select v-model="f.billing" class="select"><option value="fixed">Fixed price</option><option value="per_unit">Per unit (e.g. per image)</option></select>
        </div>
        <div class="field span-2"><label>Description on invoice</label><input v-model="f.description" class="input" placeholder="Short line shown under the item name" /></div>

        <template v-if="f.billing === 'fixed'">
            <div class="field"><label>Price (Rs){{ catalog ? ' · optional' : '' }}</label><input v-model="f.unit_price" type="number" min="0" step="any" class="input" :required="!catalog && !f.is_free" placeholder="Set the price" /></div>
        </template>
        <template v-else>
            <div class="field"><label>Rate per unit (Rs){{ catalog ? ' · optional' : '' }}</label><input v-model="f.unit_price" type="number" min="0" step="any" class="input" :required="!catalog && !f.is_free" placeholder="Set the rate" /></div>
            <div class="field"><label>Unit name</label><input v-model="f.unit" class="input" placeholder="image" /></div>
            <div v-if="!catalog" class="field"><label>Quantity (leave empty to charge on delivery)</label><input v-model="f.quantity" type="number" min="0" step="any" class="input" /></div>
        </template>
        <div class="field"><label>Our cost (Rs){{ catalog ? ' · optional' : '' }}</label><input v-model="f.cost" type="number" min="0" step="any" class="input" :required="!catalog" placeholder="What it costs us (0 if nothing)" /></div>

        <div v-if="!catalog" class="span-2 row wrap" style="gap: 22px">
            <label class="check"><input v-model="f.is_free" type="checkbox" /> Free of charge</label>
            <label class="check"><input v-model="f.is_addon" type="checkbox" /> Add-on</label>
            <span class="muted small">{{ hint }}</span>
        </div>

        <details v-if="!catalog" class="more span-2">
            <summary>Invoice wording (optional)</summary>
            <div class="form-grid" style="margin-top: 10px">
                <div class="field"><label>Rate text</label><input v-model="f.rate_text" class="input" placeholder="e.g. 15,700 / 3 yrs" /></div>
                <div class="field"><label>Label for the totals box</label><input v-model="f.short_label" class="input" placeholder="e.g. Image / logo changes" /></div>
                <div v-if="f.billing === 'per_unit'" class="field span-2"><label>Override total (Rs)</label><input v-model="f.amount" type="number" min="0" step="any" class="input" placeholder="Use only if the total is not quantity × rate" /></div>
            </div>
        </details>
    </div>
</template>
