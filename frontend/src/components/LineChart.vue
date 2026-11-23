<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { fmt, short } from '../utils';

const props = defineProps({ labels: Array, values: Array, color: { type: String, default: '#1d6b3a' }, name: { type: String, default: 'Profit' }, height: { type: Number, default: 260 } });

const box = ref(null);
const w = ref(640);
const hover = ref(-1);
let ro;
onMounted(() => {
    w.value = box.value.clientWidth || 640;
    ro = new ResizeObserver(() => (w.value = box.value.clientWidth || 640));
    ro.observe(box.value);
});
onBeforeUnmount(() => ro?.disconnect());

const pad = { l: 46, r: 12, t: 12, b: 26 };
const lo = computed(() => Math.min(0, ...props.values));
const hi = computed(() => Math.max(1, ...props.values));
const step = computed(() => {
    const raw = (hi.value - lo.value) / 4 || 1;
    const p = 10 ** Math.floor(Math.log10(raw));
    const n = raw / p;

    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
});
const base = computed(() => Math.floor(lo.value / step.value) * step.value);
const top = computed(() => base.value + step.value * 4);
const innerW = computed(() => w.value - pad.l - pad.r);
const innerH = computed(() => props.height - pad.t - pad.b);
const x = (i) => pad.l + (props.values.length === 1 ? innerW.value / 2 : (i * innerW.value) / (props.values.length - 1));
const y = (v) => pad.t + innerH.value - ((v - base.value) / (top.value - base.value)) * innerH.value;
const line = computed(() => props.values.map((v, i) => `${i ? 'L' : 'M'}${x(i)},${y(v)}`).join(' '));
const area = computed(() => `${line.value} L${x(props.values.length - 1)},${y(base.value)} L${x(0)},${y(base.value)} Z`);
const ticks = computed(() => [0, 1, 2, 3, 4].map((i) => base.value + i * step.value));
</script>

<template>
    <div ref="box" class="chart">
        <svg :width="w" :height="height" role="img">
            <g v-for="t in ticks" :key="t">
                <line :x1="pad.l" :x2="w - pad.r" :y1="y(t)" :y2="y(t)" stroke="#eceef1" />
                <text :x="pad.l - 8" :y="y(t) + 4" text-anchor="end">{{ short(t) }}</text>
            </g>
            <path :d="area" :fill="color" opacity=".09" />
            <path :d="line" fill="none" :stroke="color" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" />
            <circle v-for="(v, i) in values" :key="i" :cx="x(i)" :cy="y(v)" :r="hover === i ? 5 : 3" :fill="hover === i ? color : '#fff'" :stroke="color" stroke-width="2" />
            <text v-for="(l, i) in labels" :key="l + i" :x="x(i)" :y="height - 6" text-anchor="middle">{{ l }}</text>
            <rect v-for="(l, i) in labels" :key="'h' + i" :x="x(i) - innerW / labels.length / 2" :y="pad.t" :width="innerW / labels.length" :height="innerH" fill="transparent" @mouseenter="hover = i" @mouseleave="hover = -1" />
        </svg>
        <div v-if="hover >= 0" class="tip" :style="{ left: x(hover) + 'px', top: y(values[hover]) - 8 + 'px' }">
            <b>{{ labels[hover] }}</b>
            <div>{{ name }}: Rs {{ fmt(values[hover]) }}</div>
        </div>
    </div>
</template>
